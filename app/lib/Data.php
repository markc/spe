<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

/**
 * Server-side datatable helper - sort, search, filter, paginate
 *
 * Usage:
 *   $data = (new Data($db, 'users'))
 *       ->cols('id,login,fname,lname,acl,created,updated')
 *       ->sort('updated')
 *       ->search('login,fname,lname')
 *       ->paginate()
 *       ->fetch();
 *
 *   // In view:
 *   Data::th('login', 'Email', $data);
 *   Data::pg($data['pagination']);
 */
final class Data
{
    private string $cols = '*';
    private string $sortCol = 'id';
    private string $sortDir = 'DESC';
    private array $searchCols = [];
    private array $filters = [];
    private string $baseWhere = '1=1';
    private array $baseParams = [];
    private int $page = 1;
    private int $perPage = 10;
    private bool $paginate = true;

    public function __construct(
        private Db $db,
        private string $table,
    ) {
        // Read URL params
        $this->page = max(1, (int) ($_GET['page'] ?? 1));
        $this->sortCol = $_GET['sort'] ?? $this->sortCol;
        $this->sortDir = strtoupper($_GET['dir'] ?? $this->sortDir) === 'ASC' ? 'ASC' : 'DESC';
    }

    // === Builder Methods ===

    public function cols(string $cols): self
    {
        $this->cols = $cols;
        return $this;
    }

    public function sort(string $col, string $dir = 'DESC'): self
    {
        $this->sortCol = $_GET['sort'] ?? $col;
        $this->sortDir = strtoupper($_GET['dir'] ?? $dir) === 'ASC' ? 'ASC' : 'DESC';
        return $this;
    }

    public function search(string $cols): self
    {
        $this->searchCols = array_map('trim', explode(',', $cols));
        return $this;
    }

    public function filter(string $col, mixed $value): self
    {
        $this->filters[$col] = $value;
        return $this;
    }

    public function where(string $where, array $params = []): self
    {
        $this->baseWhere = $where;
        $this->baseParams = $params;
        return $this;
    }

    public function perPage(int $n): self
    {
        $this->perPage = $_GET['pp'] ?? $n;
        return $this;
    }

    public function paginate(bool $enabled = true): self
    {
        $this->paginate = $enabled;
        return $this;
    }

    // === Fetch Data ===

    public function fetch(): array
    {
        [$where, $params] = $this->buildWhere();

        // Count total
        $total = (int) $this->db->read($this->table, 'COUNT(*)', $where, $params, QueryType::Col);

        // Build query
        $sql = "$where ORDER BY {$this->sortCol} {$this->sortDir}";

        if ($this->paginate) {
            $offset = ($this->page - 1) * $this->perPage;
            $sql .= ' LIMIT :_limit OFFSET :_offset';
            $params['_limit'] = $this->perPage;
            $params['_offset'] = $offset;
        }

        $items = $this->db->read($this->table, $this->cols, $sql, $params, QueryType::All);

        return [
            'items' => $items,
            'total' => $total,
            'sort' => [
                'col' => $this->sortCol,
                'dir' => $this->sortDir,
            ],
            'search' => trim($_GET['q'] ?? ''),
            'filters' => $this->filters,
            'pagination' => [
                'page' => $this->page,
                'perPage' => $this->perPage,
                'total' => $total,
                'pages' => $this->paginate ? (int) ceil($total / $this->perPage) : 1,
            ],
        ];
    }

    private function buildWhere(): array
    {
        $conditions = [$this->baseWhere];
        $params = $this->baseParams;

        // Search
        $q = trim($_GET['q'] ?? '');
        if ($q && $this->searchCols) {
            $searchConditions = array_map(static fn($c) => "$c LIKE :_q", $this->searchCols);
            $conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            $params['_q'] = "%$q%";
        }

        // Filters
        foreach ($this->filters as $col => $value) {
            if (is_array($value)) {
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $key = "_f_{$col}_{$i}";
                    $placeholders[] = ":$key";
                    $params[$key] = $v;
                }
                $conditions[] = "$col IN (" . implode(',', $placeholders) . ')';
            } else {
                $params["_f_$col"] = $value;
                $conditions[] = "$col = :_f_$col";
            }
        }

        return [implode(' AND ', $conditions), $params];
    }

    // === Static View Helpers ===

    /**
     * Sortable table header
     */
    public static function th(string $col, string $label, array $data, string $class = ''): string
    {
        $current = $data['sort']['col'] ?? '';
        $dir = $data['sort']['dir'] ?? 'DESC';

        $isActive = $current === $col;
        $newDir = $isActive && $dir === 'ASC' ? 'DESC' : 'ASC';
        $arrow = $isActive ? ($dir === 'ASC' ? ' ▲' : ' ▼') : '';

        $params = $_GET;
        $params['sort'] = $col;
        $params['dir'] = $newDir;
        unset($params['page']); // Reset to page 1 on sort change

        $qs = http_build_query($params);
        $activeClass = $isActive ? ' sortable-active' : '';
        $cls = $class ? " class=\"$class$activeClass\"" : ($activeClass ? " class=\"$activeClass\"" : '');

        return "<th$cls><a href=\"?$qs\">$label$arrow</a></th>";
    }

    /**
     * Non-sortable table header
     */
    public static function thPlain(string $label, string $class = ''): string
    {
        $cls = $class ? " class=\"$class\"" : '';
        return "<th$cls>$label</th>";
    }

    /**
     * Pagination links
     */
    public static function pg(array $p, string $class = 'flex mt-2 justify-center gap-sm'): string
    {
        if ($p['pages'] <= 1)
            return '';

        $params = $_GET;
        $html = "<div class=\"$class\">";

        // Previous
        if ($p['page'] > 1) {
            $params['page'] = $p['page'] - 1;
            $html .= '<a href="?' . http_build_query($params) . '" class="btn">« Prev</a>';
        }

        // Page indicator
        $html .= "<span class=\"pagination-info\">Page {$p['page']} of {$p['pages']} ({$p['total']} items)</span>";

        // Next
        if ($p['page'] < $p['pages']) {
            $params['page'] = $p['page'] + 1;
            $html .= '<a href="?' . http_build_query($params) . '" class="btn">Next »</a>';
        }

        return $html . '</div>';
    }

    /**
     * Search input
     */
    public static function searchInput(string $placeholder = 'Search...', string $class = 'w-200'): string
    {
        $q = htmlspecialchars($_GET['q'] ?? '');
        $params = $_GET;
        unset($params['q'], $params['page']);
        $hidden = '';
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                continue;
            }

            $hidden .= "<input type=\"hidden\" name=\"$k\" value=\"" . htmlspecialchars($v) . "\">";
        }

        $clear = $q
            ? '<button type="button" class="btn" onclick="this.form.q.value=\'\';this.form.submit()">✕</button>'
            : '';

        return <<<HTML
        <form class="flex gap-sm">
            $hidden
            <input type="search" name="q" value="$q" placeholder="$placeholder" class="$class">
            <button type="submit" class="btn">🔍</button>
            $clear
        </form>
        HTML;
    }

    /**
     * Per-page selector
     */
    public static function perPageSelect(array $options = [10, 25, 50, 100], int $current = 10): string
    {
        $params = $_GET;
        unset($params['page']); // Reset to page 1

        $opts = '';
        foreach ($options as $n) {
            $selected = $n == ($params['pp'] ?? $current) ? ' selected' : '';
            $opts .= "<option value=\"$n\"$selected>$n</option>";
        }

        $params['pp'] = '__PP__';
        $qs = http_build_query($params);

        return <<<HTML
        <select onchange="location='?{$qs}'.replace('__PP__', this.value)">
            $opts
        </select>
        HTML;
    }

    /**
     * Table wrapper with search, per-page, and pagination
     */
    public static function wrap(string $tableHtml, array $data, array $actions = []): string
    {
        $search = self::searchInput();
        $perPage = self::perPageSelect();
        $pagination = self::pg($data['pagination']);

        $actionHtml = '';
        foreach ($actions as $label => $href) {
            $actionHtml .= "<a href=\"$href\" class=\"btn\">$label</a>";
        }

        return <<<HTML
        <div class="datatable">
            <div class="datatable-header flex justify-between mb-2">
                <div class="flex gap-sm">$search $perPage</div>
                <div class="flex gap-sm">$actionHtml</div>
            </div>
            $tableHtml
            $pagination
        </div>
        HTML;
    }
}
