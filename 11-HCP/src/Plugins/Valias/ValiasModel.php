<?php declare(strict_types=1);

// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Valias;

use SPE\HCP\Core\Ctx;
use SPE\HCP\Core\Plugin;
use SPE\HCP\Lib\Valias;

/**
 * Mail alias management - uses lib/Valias.php (DB-only operations).
 */
final class ValiasModel extends Plugin
{
    private array $in = [
        'source' => '',
        'target' => '',
        'domain' => '',
    ];

    public function __construct(
        protected Ctx $ctx,
    ) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) {
            $v = $_REQUEST[$k] ?? $v;
        }
    }

    public function create(): array
    {
        if ($_POST) {
            $source = strtolower(trim($this->in['source']));
            $target = strtolower(trim($this->in['target']));

            if (empty($source) || empty($target)) {
                return ['error' => 'Source and target are required'];
            }

            // Handle multiple targets (comma or newline separated)
            $targets = preg_split('/[,\n]+/', $target, -1, PREG_SPLIT_NO_EMPTY);
            $targets = array_map('trim', $targets);

            $result = Valias::add($source, $targets);

            if ($result['success']) {
                header('Location: ?o=Valias');
                exit();
            }

            return ['error' => $result['error']];
        }

        // Get available domains for dropdown
        $domains = $this->getActiveDomains();
        return ['domains' => $domains];
    }

    public function read(): array
    {
        $source = strtolower(trim($this->in['source']));
        if (empty($source)) {
            return ['error' => 'Invalid alias source'];
        }

        $result = Valias::show($source);

        if (!$result['success']) {
            return ['error' => $result['error']];
        }

        return $result;
    }

    public function update(): array
    {
        $source = strtolower(trim($this->in['source']));
        if (empty($source)) {
            return ['error' => 'Invalid alias source'];
        }

        if ($_POST) {
            $action = $_POST['action'] ?? '';

            if ($action === 'targets' && !empty($_POST['target'])) {
                $targets = preg_split('/[,\n]+/', $_POST['target'], -1, PREG_SPLIT_NO_EMPTY);
                $targets = array_map('trim', $targets);

                $result = Valias::update($source, $targets);
                return (
                    $result['success']
                        ? ['success' => 'Alias updated', 'source' => $source]
                        : ['error' => $result['error']]
                );
            }

            if ($action === 'toggle') {
                $active = ($_POST['active'] ?? '0') === '1';
                $result = Valias::setActive($source, $active);
                return (
                    $result['success']
                        ? ['success' => ($active ? 'Enabled' : 'Disabled') . ' alias', 'source' => $source]
                        : ['error' => $result['error']]
                );
            }
        }

        // Get current alias for edit form
        return Valias::show($source);
    }

    public function delete(): array
    {
        $source = strtolower(trim($this->in['source']));
        if (empty($source)) {
            return ['error' => 'Invalid alias source'];
        }

        if ($_POST && isset($_POST['confirm'])) {
            $result = Valias::del($source);

            if ($result['success']) {
                header('Location: ?o=Valias');
                exit();
            }

            return ['error' => $result['error']];
        }

        return ['source' => $source, 'confirm_required' => true];
    }

    public function list(): array
    {
        $domain = $this->in['domain'] ?: null;
        $aliases = Valias::list($domain);

        // Get domains for filter dropdown
        $domains = $this->getActiveDomains();

        return [
            'items' => $aliases,
            'total' => count($aliases),
            'domains' => $domains,
            'filter_domain' => $domain,
        ];
    }

    private function getActiveDomains(): array
    {
        try {
            $pdo = new \PDO('sqlite:/srv/.local/sqlite/sysadm.db', null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $pdo->query('SELECT domain FROM vhosts WHERE active = 1 ORDER BY domain');
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }
}
