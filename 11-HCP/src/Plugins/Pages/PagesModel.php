<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\HCP\Plugins\Pages;

use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;
use SPE\HCP\Core\Ctx;
use SPE\HCP\Core\Plugin;
use SPE\HCP\Plugins\Categories\CategoriesModel;

final class PagesModel extends Plugin
{
    private ?Db $dbh = null;
    private array $in = ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'icon' => ''];

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
        if (!Util::is_adm()) {
            Util::log('Admin access required');
            Util::redirect('?o=Home');
        }

        if (Util::is_post()) {
            $slug = $this->in['slug'] ?: $this->slugify($this->in['title']);

            // Check slug uniqueness
            $existing = $this->dbh->read(
                'posts',
                'id',
                'slug = :slug AND type = :type',
                ['slug' => $slug, 'type' => 'page'],
                QueryType::One,
            );
            if ($existing) {
                Util::log('A page with this slug already exists');
                return [
                    'title' => $this->in['title'],
                    'slug' => $slug,
                    'content' => $this->in['content'],
                    'all_categories' => CategoriesModel::getAll($this->dbh),
                    'post_categories' => [],
                ];
            }

            $data = [
                'title' => $this->in['title'],
                'slug' => $slug,
                'content' => $this->in['content'],
                'icon' => $this->in['icon'],
                'author' => $_SESSION['usr']['fname'] ?: $_SESSION['usr']['login'],
                'author_id' => $_SESSION['usr']['id'],
                'type' => 'page',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            $pageId = $this->dbh->create('posts', $data);

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            CategoriesModel::syncForPost($this->dbh, $pageId, $categoryIds);

            Util::log('Page created', 'success');
            Util::redirect('?o=Pages');
        }
        return [
            'all_categories' => CategoriesModel::getAll($this->dbh),
            'post_categories' => [],
        ];
    }

    #[\Override]
    public function read(): array
    {
        // Can read by id or slug
        $slug = $this->in['slug'] ?? $_GET['slug'] ?? '';
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);

        if ($slug) {
            $page = $this->dbh->read(
                'posts',
                '*',
                'slug = :slug AND type = :type',
                ['slug' => $slug, 'type' => 'page'],
                QueryType::One,
            ) ?: [];
        } else {
            $page = $this->dbh->read(
                'posts',
                '*',
                'id = :id AND type = :type',
                ['id' => $id, 'type' => 'page'],
                QueryType::One,
            ) ?: [];
        }

        if ($page) {
            $page['categories'] = CategoriesModel::getForPost($this->dbh, (int) $page['id']);
        }
        return $page;
    }

    #[\Override]
    public function update(): array
    {
        if (!Util::is_adm()) {
            Util::log('Admin access required');
            Util::redirect('?o=Home');
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $page = $this->dbh->read(
            'posts',
            '*',
            'id = :id AND type = :type',
            ['id' => $id, 'type' => 'page'],
            QueryType::One,
        );

        if (!$page) {
            Util::log('Page not found');
            Util::redirect('?o=Pages');
        }

        if (Util::is_post()) {
            $slug = $this->in['slug'] ?: $this->slugify($this->in['title']);

            // Check slug uniqueness (excluding current page)
            $existing = $this->dbh->read(
                'posts',
                'id',
                'slug = :slug AND type = :type AND id != :id',
                ['slug' => $slug, 'type' => 'page', 'id' => $id],
                QueryType::One,
            );
            if ($existing) {
                Util::log('A page with this slug already exists');
                return array_merge($page, [
                    'title' => $this->in['title'],
                    'slug' => $slug,
                    'content' => $this->in['content'],
                    'all_categories' => CategoriesModel::getAll($this->dbh),
                    'post_categories' => CategoriesModel::getForPost($this->dbh, $id),
                ]);
            }

            $data = [
                'title' => $this->in['title'],
                'slug' => $slug,
                'content' => $this->in['content'],
                'icon' => $this->in['icon'],
                'updated' => date('Y-m-d H:i:s'),
            ];
            $this->dbh->update('posts', $data, 'id = :id', ['id' => $id]);

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            CategoriesModel::syncForPost($this->dbh, $id, $categoryIds);

            Util::log('Page updated', 'success');
            Util::redirect('?o=Pages');
        }

        $page['all_categories'] = CategoriesModel::getAll($this->dbh);
        $page['post_categories'] = CategoriesModel::getForPost($this->dbh, $id);
        return $page;
    }

    #[\Override]
    public function delete(): array
    {
        if (!Util::is_adm()) {
            Util::log('Admin access required');
            Util::redirect('?o=Home');
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $page = $this->dbh->read(
            'posts',
            '*',
            'id = :id AND type = :type',
            ['id' => $id, 'type' => 'page'],
            QueryType::One,
        );

        if (!$page) {
            Util::log('Page not found');
            Util::redirect('?o=Pages');
        }

        // Prevent deletion of core pages
        if (in_array($page['slug'], ['home', 'about', 'contact'])) {
            Util::log('Cannot delete core pages');
            Util::redirect('?o=Pages');
        }

        $this->dbh->delete('posts', 'id = :id', ['id' => $id]);
        Util::log('Page deleted', 'success');
        Util::redirect('?o=Pages');
    }

    #[\Override]
    public function list(): array
    {
        return [
            'items' => $this->dbh->read(
                'posts',
                '*',
                'type = :type ORDER BY title ASC',
                ['type' => 'page'],
                QueryType::All,
            ),
        ];
    }

    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    }
}
