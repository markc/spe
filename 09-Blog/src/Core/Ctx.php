<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

use SPE\App\{Db, QueryType};

final class Ctx {
    public array $nav1;
    public array $navAdmin;
    public array $nav2;
    public array $navPages;
    public array $navGroups = [];

    public function __construct(
        public string $email = '',
        public string $buf = '',
        public array $ary = [],
        public array $nav = [],
        public array $in = ['id' => 0, 'l' => '', 'm' => 'list', 'o' => 'Home', 't' => 'Simple', 'x' => ''],
        public array $out = [
            'doc' => 'SPE::09', 'css' => '', 'log' => '', 'nav1' => '', 'nav2' => '', 'nav3' => '',
            'main' => 'Error: missing plugin!',
            'head' => 'Blog PHP Example',
            'foot' => '© 2015-2025 Mark Constable (MIT License)',
            'js' => ''
        ],
        public ?PluginLoader $loader = null,
    ) {
        // Set email from FQDN for outgoing mail
        $this->email = 'noreply@' . (trim(`hostname -f`) ?: 'localhost');

        // Auto-discover plugins and themes
        $this->loader = new PluginLoader();
        $this->navPages = $this->buildNavPages();           // Pages from database
        $this->nav1 = $this->loader->buildNav1();           // Plugin nav (Blog only now)
        $this->navAdmin = $this->loader->buildNavAdmin();   // Admin nav
        $this->nav2 = $this->loader->buildNav2();           // Themes
    }

    private function buildNavPages(): array {
        $db = new Db('blog');

        // Get pages with icon
        $pages = $db->read('posts', 'id, title, slug, icon', 'type = :type ORDER BY id ASC', ['type' => 'page'], QueryType::All) ?: [];

        // Get all categories indexed by ID
        $categories = $db->read('categories', '*', '1=1 ORDER BY name', [], QueryType::All) ?: [];
        $catById = $categories |> (fn($cats) => array_column($cats, null, 'id'));

        // Build grouped nav structure
        $grouped = ['main' => []];  // 'main' category pages go to top-level

        foreach ($pages as $page) {
            // Use page icon or default to document emoji
            $icon = $page['icon'] ?: '📄';
            $label = "{$icon} {$page['title']}";

            // Get categories for this page
            $pageCats = $db->read('post_categories', 'category_id', 'post_id = :id', ['id' => $page['id']], QueryType::All) ?: [];

            if (empty($pageCats)) {
                // Uncategorized pages go to main nav
                $grouped['main'][] = [$label, "?p={$page['slug']}"];
            } else {
                // Use first category for grouping
                $catId = $pageCats[0]['category_id'];
                $catSlug = $catById[$catId]['slug'] ?? 'main';

                if ($catSlug === 'main') {
                    $grouped['main'][] = [$label, "?p={$page['slug']}"];
                } else {
                    if (!isset($grouped[$catSlug])) {
                        $grouped[$catSlug] = [
                            'name' => $catById[$catId]['name'] ?? $catSlug,
                            'items' => []
                        ];
                    }
                    $grouped[$catSlug]['items'][] = [$label, "?p={$page['slug']}"];
                }
            }
        }

        // Add Blog link to main pages
        $grouped['main'][] = ['📰 Blog', '?o=Blog'];

        // Store grouped categories separately
        $this->navGroups = $grouped;
        return $grouped['main'];
    }
}
