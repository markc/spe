<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Docs;

use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;
use SPE\Blog\Core\Ctx;
use SPE\Blog\Core\Plugin;
use SPE\Blog\Plugins\Categories\CategoriesModel;

final class DocsModel extends Plugin
{
    private ?Db $dbh = null;
    private array $in = [
        'id' => 0,
        'title' => '',
        'slug' => '',
        'content' => '',
        'excerpt' => '',
        'featured_image' => '',
        'icon' => '',
    ];

    public function __construct(
        protected Ctx $ctx,
    ) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v)
            $v = $_REQUEST[$k] ?? $v;
        $this->dbh = new Db('blog');
    }

    #[\Override]
    public function create(): array
    {
        if (!Util::is_usr()) {
            Util::log('Login required');
            Util::redirect('?o=Auth');
        }

        if (Util::is_post()) {
            $slug = $this->in['slug'] ?: $this->slugify($this->in['title']);

            // Check slug uniqueness
            $existing = $this->dbh->read(
                'posts',
                'id',
                'slug = :slug AND type = :type',
                ['slug' => $slug, 'type' => 'doc'],
                QueryType::One,
            );
            if ($existing) {
                Util::log('A doc with this slug already exists');
                return array_merge($this->in, [
                    'slug' => $slug,
                    'all_categories' => CategoriesModel::getAll($this->dbh),
                    'post_categories' => [],
                ]);
            }

            $data = [
                'title' => $this->in['title'],
                'slug' => $slug,
                'content' => $this->in['content'], // This is the PATH to the markdown file
                'excerpt' => $this->in['excerpt'],
                'featured_image' => $this->in['featured_image'],
                'icon' => $this->in['icon'],
                'author' => $_SESSION['usr']['fname'] ?: $_SESSION['usr']['login'],
                'author_id' => $_SESSION['usr']['id'],
                'type' => 'doc',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            $docId = $this->dbh->create('posts', $data);

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            CategoriesModel::syncForPost($this->dbh, $docId, $categoryIds);

            Util::log('Doc created', 'success');
            Util::redirect('?o=Docs');
        }
        return [
            'all_categories' => CategoriesModel::getAll($this->dbh),
            'post_categories' => [],
        ];
    }

    #[\Override]
    public function read(): array
    {
        $slug = $_GET['slug'] ?? '';
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);

        if ($slug) {
            $doc = $this->dbh->read(
                'posts',
                '*',
                'slug = :slug AND type = :type',
                ['slug' => $slug, 'type' => 'doc'],
                QueryType::One,
            ) ?: [];
        } else {
            $doc = $this->dbh->read(
                'posts',
                '*',
                'id = :id AND type = :type',
                ['id' => $id, 'type' => 'doc'],
                QueryType::One,
            ) ?: [];
        }

        if ($doc) {
            $doc['categories'] = CategoriesModel::getForPost($this->dbh, (int) $doc['id']);

            // Read markdown content from file path stored in content field
            $doc['file_content'] = $this->readMarkdownFile($doc['content']);
            $doc['file_exists'] = $doc['file_content'] !== null;

            // Get prev/next docs
            $doc['prev'] = $this->dbh->read(
                'posts',
                'id, title, slug',
                'type = :type AND created < :created ORDER BY created DESC LIMIT 1',
                ['type' => 'doc', 'created' => $doc['created']],
                QueryType::One,
            );
            $doc['next'] = $this->dbh->read(
                'posts',
                'id, title, slug',
                'type = :type AND created > :created ORDER BY created ASC LIMIT 1',
                ['type' => 'doc', 'created' => $doc['created']],
                QueryType::One,
            );
        }
        return $doc;
    }

    #[\Override]
    public function update(): array
    {
        if (!Util::is_usr()) {
            Util::log('Login required');
            Util::redirect('?o=Auth');
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $doc = $this->dbh->read(
            'posts',
            '*',
            'id = :id AND type = :type',
            ['id' => $id, 'type' => 'doc'],
            QueryType::One,
        );

        if (!$doc) {
            Util::log('Doc not found');
            Util::redirect('?o=Docs');
        }

        if (Util::is_post()) {
            $slug = $this->in['slug'] ?: $this->slugify($this->in['title']);

            // Check slug uniqueness (excluding current doc)
            $existing = $this->dbh->read(
                'posts',
                'id',
                'slug = :slug AND type = :type AND id != :id',
                ['slug' => $slug, 'type' => 'doc', 'id' => $id],
                QueryType::One,
            );
            if ($existing) {
                Util::log('A doc with this slug already exists');
                return array_merge($doc, $this->in, [
                    'slug' => $slug,
                    'all_categories' => CategoriesModel::getAll($this->dbh),
                    'post_categories' => CategoriesModel::getForPost($this->dbh, $id),
                ]);
            }

            $data = [
                'title' => $this->in['title'],
                'slug' => $slug,
                'content' => $this->in['content'],
                'excerpt' => $this->in['excerpt'],
                'featured_image' => $this->in['featured_image'],
                'icon' => $this->in['icon'],
                'updated' => date('Y-m-d H:i:s'),
            ];
            $this->dbh->update('posts', $data, 'id = :id', ['id' => $id]);

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            CategoriesModel::syncForPost($this->dbh, $id, $categoryIds);

            Util::log('Doc updated', 'success');
            Util::redirect('?o=Docs');
        }

        $doc['all_categories'] = CategoriesModel::getAll($this->dbh);
        $doc['post_categories'] = CategoriesModel::getForPost($this->dbh, $id);
        return $doc;
    }

    #[\Override]
    public function delete(): array
    {
        if (!Util::is_usr()) {
            Util::log('Login required');
            Util::redirect('?o=Auth');
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $doc = $this->dbh->read(
            'posts',
            '*',
            'id = :id AND type = :type',
            ['id' => $id, 'type' => 'doc'],
            QueryType::One,
        );

        if (!$doc) {
            Util::log('Doc not found');
            Util::redirect('?o=Docs');
        }

        // Delete category associations
        $this->dbh->delete('post_categories', 'post_id = :id', ['id' => $id]);
        $this->dbh->delete('posts', 'id = :id', ['id' => $id]);

        Util::log('Doc deleted', 'success');
        Util::redirect('?o=Docs');
    }

    #[\Override]
    public function list(): array
    {
        // Get all docs with their categories
        $docs = $this->dbh->read('posts', '*', 'type = :type ORDER BY created DESC', ['type' => 'doc'], QueryType::All)
        ?: [];

        // Check file existence and group by category
        $grouped = [];
        foreach ($docs as &$doc) {
            $doc['file_exists'] = $this->fileExists($doc['content']);
            $doc['categories'] = CategoriesModel::getForPost($this->dbh, (int) $doc['id']);

            // Group by first category or 'Uncategorized'
            $catName = !empty($doc['categories']) ? $doc['categories'][0]['name'] : 'Uncategorized';
            $grouped[$catName][] = $doc;
        }

        return [
            'items' => $docs,
            'grouped' => $grouped,
        ];
    }

    private function readMarkdownFile(string $path): ?string
    {
        if (empty($path))
            return null;

        // Handle relative paths (relative to spe project root, not 09-Blog)
        if (!str_starts_with($path, '/')) {
            $path = dirname(__DIR__, 4) . '/' . $path;
        }

        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }

        return file_get_contents($path);
    }

    private function fileExists(string $path): bool
    {
        if (empty($path))
            return false;

        if (!str_starts_with($path, '/')) {
            $path = dirname(__DIR__, 4) . '/' . $path;
        }

        return file_exists($path) && is_readable($path);
    }

    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    }
}
