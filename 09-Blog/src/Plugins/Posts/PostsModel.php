<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Posts;

use SPE\App\{Db, QueryType, Util};
use SPE\Blog\Core\{Ctx, Plugin};
use SPE\Blog\Plugins\Categories\CategoriesModel;

final class PostsModel extends Plugin {
    private const int DEFAULT_PER_PAGE = 10;
    private ?Db $dbh = null;
    private array $in = ['id' => 0, 'title' => '', 'content' => '', 'excerpt' => '', 'featured_image' => '', 'author_id' => 0];

    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) $v = $_REQUEST[$k] ?? $v;
        $this->dbh = new Db('blog');
    }

    // Check if current user can edit this post (owner or admin)
    private function canEdit(array $post): bool {
        if (!Util::is_usr()) return false;
        if (Util::is_adm()) return true;
        return (int)$post['author_id'] === (int)$_SESSION['usr']['id'];
    }

    #[\Override] public function create(): array {
        if (!Util::is_usr()) {
            Util::log('Please login to create posts');
            Util::redirect('?o=Auth');
        }

        if (Util::is_post()) {
            $data = [
                'title' => $this->in['title'],
                'content' => $this->in['content'],
                'excerpt' => $this->in['excerpt'],
                'featured_image' => $this->in['featured_image'],
                'author' => $_SESSION['usr']['fname'] ?: $_SESSION['usr']['login'],
                'author_id' => $_SESSION['usr']['id'],
                'type' => 'post',
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ];
            $postId = $this->dbh->create('posts', $data);

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            CategoriesModel::syncForPost($this->dbh, $postId, $categoryIds);

            Util::log('Post created', 'success');
            Util::redirect('?o=Posts');
        }
        return [
            'can_edit' => true,
            'all_categories' => CategoriesModel::getAll($this->dbh),
            'post_categories' => []
        ];
    }

    #[\Override] public function read(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $post = $this->dbh->read('posts', '*', 'id = :id AND type = :type', ['id' => $id, 'type' => 'post'], QueryType::One) ?: [];
        if ($post) {
            $post['can_edit'] = $this->canEdit($post);
            $post['categories'] = CategoriesModel::getForPost($this->dbh, $id);
        }
        return $post;
    }

    #[\Override] public function update(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $post = $this->dbh->read('posts', '*', 'id = :id AND type = :type', ['id' => $id, 'type' => 'post'], QueryType::One);

        if (!$post) {
            Util::log('Post not found');
            Util::redirect('?o=Posts');
        }

        if (!$this->canEdit($post)) {
            Util::log('You do not have permission to edit this post');
            Util::redirect('?o=Posts&m=read&id=' . $id);
        }

        if (Util::is_post()) {
            $data = [
                'title' => $this->in['title'],
                'content' => $this->in['content'],
                'excerpt' => $this->in['excerpt'],
                'featured_image' => $this->in['featured_image'],
                'updated' => date('Y-m-d H:i:s')
            ];
            $this->dbh->update('posts', $data, 'id = :id', ['id' => $id]);

            // Sync categories
            $categoryIds = $_POST['categories'] ?? [];
            CategoriesModel::syncForPost($this->dbh, $id, $categoryIds);

            Util::log('Post updated', 'success');
            Util::redirect('?o=Posts&m=read&id=' . $id);
        }

        $post['can_edit'] = true;
        $post['all_categories'] = CategoriesModel::getAll($this->dbh);
        $post['post_categories'] = CategoriesModel::getForPost($this->dbh, $id);
        return $post;
    }

    #[\Override] public function delete(): array {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $post = $this->dbh->read('posts', '*', 'id = :id AND type = :type', ['id' => $id, 'type' => 'post'], QueryType::One);

        if (!$post) {
            Util::log('Post not found');
            Util::redirect('?o=Posts');
        }

        if (!$this->canEdit($post)) {
            Util::log('You do not have permission to delete this post');
            Util::redirect('?o=Posts&m=read&id=' . $id);
        }

        $this->dbh->delete('posts', 'id = :id', ['id' => $id]);
        Util::log('Post deleted', 'success');
        Util::redirect('?o=Posts');
    }

    #[\Override] public function list(): array {
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($_REQUEST['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT) ?: self::DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;
        $searchQuery = trim($_GET['q'] ?? '');
        $where = 'type = :type';
        $params = ['type' => 'post'];

        if ($searchQuery !== '') {
            $where .= ' AND (title LIKE :search OR content LIKE :search)';
            $params['search'] = '%' . $searchQuery . '%';
        }

        $total = $this->dbh->read('posts', 'COUNT(*)', $where, $params, QueryType::Col);
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        return [
            'items' => $this->dbh->read('posts', '*', $where . ' ORDER BY updated DESC, created DESC LIMIT :limit OFFSET :offset', $params, QueryType::All),
            'pagination' => ['page' => $page, 'perPage' => $perPage, 'total' => $total, 'pages' => ceil($total / $perPage)],
            'can_create' => Util::is_usr()
        ];
    }
}
