<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Categories;

use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;
use SPE\Htmx\Core\Ctx;
use SPE\Htmx\Core\Plugin;

final class CategoriesModel extends Plugin
{
    private ?Db $dbh = null;
    private array $in = ['id' => 0, 'name' => '', 'slug' => '', 'description' => ''];

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
            Util::redirect('?o=Blog');
        }

        if (Util::is_post()) {
            $name = trim($this->in['name']);
            $slug = $this->in['slug'] ?: $this->slugify($name);

            if (!$name) {
                Util::log('Category name is required');
                return ['name' => $name, 'slug' => $slug, 'description' => $this->in['description']];
            }

            // Check uniqueness
            $existing = $this->dbh->read('categories', 'id', 'slug = :slug', ['slug' => $slug], QueryType::One);
            if ($existing) {
                Util::log('A category with this slug already exists');
                return ['name' => $name, 'slug' => $slug, 'description' => $this->in['description']];
            }

            $this->dbh->create('categories', [
                'name' => $name,
                'slug' => $slug,
                'description' => $this->in['description'],
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ]);
            Util::log('Category created', 'success');
            Util::redirect('?o=Categories');
        }
        return [];
    }

    #[\Override]
    public function read(): array
    {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $category = $this->dbh->read('categories', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];

        if ($category) {
            // Get posts in this category
            $category['posts'] = $this->dbh->read(
                'posts',
                '*',
                'id IN (SELECT post_id FROM post_categories WHERE category_id = :cat_id) ORDER BY updated DESC',
                ['cat_id' => $id],
                QueryType::All,
            ) ?: [];
        }
        return $category;
    }

    #[\Override]
    public function update(): array
    {
        if (!Util::is_adm()) {
            Util::log('Admin access required');
            Util::redirect('?o=Blog');
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $category = $this->dbh->read('categories', '*', 'id = :id', ['id' => $id], QueryType::One);

        if (!$category) {
            Util::log('Category not found');
            Util::redirect('?o=Categories');
        }

        if (Util::is_post()) {
            $name = trim($this->in['name']);
            $slug = $this->in['slug'] ?: $this->slugify($name);

            if (!$name) {
                Util::log('Category name is required');
                return array_merge($category, [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $this->in['description'],
                ]);
            }

            // Check uniqueness (excluding current)
            $existing = $this->dbh->read(
                'categories',
                'id',
                'slug = :slug AND id != :id',
                ['slug' => $slug, 'id' => $id],
                QueryType::One,
            );
            if ($existing) {
                Util::log('A category with this slug already exists');
                return array_merge($category, [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $this->in['description'],
                ]);
            }

            $this->dbh->update(
                'categories',
                [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $this->in['description'],
                    'updated' => date('Y-m-d H:i:s'),
                ],
                'id = :id',
                ['id' => $id],
            );
            Util::log('Category updated', 'success');
            Util::redirect('?o=Categories');
        }
        return $category;
    }

    #[\Override]
    public function delete(): array
    {
        if (!Util::is_adm()) {
            Util::log('Admin access required');
            Util::redirect('?o=Blog');
        }

        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);

        // Prevent deletion of protected categories
        $protected = ['uncategorized', 'main'];
        $category = $this->dbh->read('categories', 'slug', 'id = :id', ['id' => $id], QueryType::One);
        if ($category && in_array($category['slug'], $protected)) {
            Util::log('Cannot delete protected category');
            Util::redirect('?o=Categories');
        }

        // Delete category (junction table entries cascade)
        $this->dbh->delete('categories', 'id = :id', ['id' => $id]);
        Util::log('Category deleted', 'success');
        Util::redirect('?o=Categories');
    }

    #[\Override]
    public function list(): array
    {
        $categories = $this->dbh->read('categories', '*', '1=1 ORDER BY name ASC', [], QueryType::All);

        // Get post counts for each category
        foreach ($categories as &$cat) {
            $count = $this->dbh->read(
                'post_categories',
                'COUNT(*)',
                'category_id = :id',
                ['id' => $cat['id']],
                QueryType::Col,
            );
            $cat['post_count'] = (int) $count;
        }

        return ['items' => $categories];
    }

    private function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    }

    // Static helper to get all categories (for use in other plugins)
    public static function getAll(Db $db): array
    {
        return $db->read('categories', '*', '1=1 ORDER BY name ASC', [], QueryType::All) ?: [];
    }

    // Static helper to get categories for a post
    public static function getForPost(Db $db, int $postId): array
    {
        return (
            $db->read(
                'categories',
                '*',
                'id IN (SELECT category_id FROM post_categories WHERE post_id = :post_id) ORDER BY name',
                ['post_id' => $postId],
                QueryType::All,
            ) ?: []
        );
    }

    // Static helper to sync categories for a post
    public static function syncForPost(Db $db, int $postId, array $categoryIds): void
    {
        // Remove existing
        $db->delete('post_categories', 'post_id = :post_id', ['post_id' => $postId]);

        // Add new
        foreach ($categoryIds as $catId) {
            $catId = (int) $catId;
            if ($catId > 0) {
                $db->create('post_categories', ['post_id' => $postId, 'category_id' => $catId]);
            }
        }
    }
}
