<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Blog;

use SPE\App\{Db, QueryType, Util};
use SPE\Blog\Core\{Ctx, Plugin};
use SPE\Blog\Plugins\Categories\CategoriesModel;

final class BlogModel extends Plugin {
    private const int DEFAULT_PER_PAGE = 9;
    private ?Db $dbh = null;
    private array $in = ['id' => 0];

    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) $v = $_REQUEST[$k] ?? $v;
        $this->dbh = new Db('blog');
    }

    // Public blog index - card grid view
    #[\Override] public function list(): array {
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = self::DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $where = 'type = :type';
        $params = ['type' => 'post'];

        $total = $this->dbh->read('posts', 'COUNT(*)', $where, $params, QueryType::Col);
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        $posts = $this->dbh->read('posts', '*', $where . ' ORDER BY created DESC LIMIT :limit OFFSET :offset', $params, QueryType::All) ?: [];

        // Generate excerpts for posts without one
        foreach ($posts as &$post) {
            if (empty($post['excerpt'])) {
                $post['excerpt'] = $this->generateExcerpt($post['content'], 100);
            }
        }

        return [
            'items' => $posts,
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => (int)ceil($total / $perPage)]
        ];
    }

    // Single post view with prev/next navigation
    #[\Override] public function read(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $post = $this->dbh->read('posts', '*', 'id = :id AND type = :type', ['id' => $id, 'type' => 'post'], QueryType::One) ?: [];

        if ($post) {
            $post['categories'] = CategoriesModel::getForPost($this->dbh, $id);

            // Get prev/next posts
            $post['prev'] = $this->dbh->read('posts', 'id, title',
                'type = :type AND created < :created ORDER BY created DESC LIMIT 1',
                ['type' => 'post', 'created' => $post['created']], QueryType::One) ?: null;

            $post['next'] = $this->dbh->read('posts', 'id, title',
                'type = :type AND created > :created ORDER BY created ASC LIMIT 1',
                ['type' => 'post', 'created' => $post['created']], QueryType::One) ?: null;
        }

        return $post;
    }

    private function generateExcerpt(string $content, int $words = 50): string {
        // Strip markdown and HTML
        $text = strip_tags($content);
        $text = preg_replace('/[#*_`\[\]()]/', '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Limit to N words
        $wordArray = explode(' ', $text);
        if (count($wordArray) > $words) {
            $wordArray = array_slice($wordArray, 0, $words);
            return implode(' ', $wordArray) . '...';
        }
        return $text;
    }

    // Unused CRUD methods - redirect to Posts plugin
    #[\Override] public function create(): array {
        Util::redirect('?o=Posts&m=create');
    }

    #[\Override] public function update(): array {
        Util::redirect('?o=Posts&m=update&id=' . $this->in['id']);
    }

    #[\Override] public function delete(): array {
        Util::redirect('?o=Posts&m=delete&id=' . $this->in['id']);
    }
}
