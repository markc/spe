<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Htmx\Plugins\Users;

use SPE\App\Db;
use SPE\App\QueryType;
use SPE\Htmx\Core\Ctx;
use SPE\Htmx\Core\Plugin;

final class UsersModel extends Plugin
{
    private const int DEFAULT_PER_PAGE = 10;

    private ?Db $dbh = null;
    private array $in = [
        'id' => 0,
        'grp' => 0,
        'acl' => 0,
        'login' => '',
        'fname' => '',
        'lname' => '',
        'altemail' => '',
        'webpw' => '',
        'otp' => '',
        'otpttl' => 0,
        'cookie' => '',
        'anote' => '',
    ];

    public function __construct(
        protected Ctx $ctx,
    ) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v)
            $v = $_REQUEST[$k] ?? $v;
        if (is_null($this->dbh)) {
            $this->dbh = new Db('users');
        }
    }

    #[\Override]
    public function create(): array
    {
        if ($_POST) {
            $data = [
                'grp' => (int) $this->in['grp'],
                'acl' => (int) $this->in['acl'],
                'login' => $this->in['login'],
                'fname' => $this->in['fname'],
                'lname' => $this->in['lname'],
                'altemail' => $this->in['altemail'],
                'webpw' => $this->in['webpw'] ? password_hash($this->in['webpw'], PASSWORD_DEFAULT) : '',
                'otp' => $this->in['otp'],
                'otpttl' => (int) $this->in['otpttl'],
                'cookie' => $this->in['cookie'],
                'anote' => $this->in['anote'],
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ];
            $this->dbh->create('users', $data);
            header('Location: ?o=Users&t=' . $this->ctx->in['t']);
            exit();
        }
        return [];
    }

    #[\Override]
    public function read(): array
    {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        return $this->dbh->read('users', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }

    #[\Override]
    public function update(): array
    {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        if ($_POST) {
            $data = [
                'grp' => (int) $this->in['grp'],
                'acl' => (int) $this->in['acl'],
                'login' => $this->in['login'],
                'fname' => $this->in['fname'],
                'lname' => $this->in['lname'],
                'altemail' => $this->in['altemail'],
                'otp' => $this->in['otp'],
                'otpttl' => (int) $this->in['otpttl'],
                'cookie' => $this->in['cookie'],
                'anote' => $this->in['anote'],
                'updated' => date('Y-m-d H:i:s'),
            ];
            if ($this->in['webpw'])
                $data['webpw'] = password_hash($this->in['webpw'], PASSWORD_DEFAULT);
            $this->dbh->update('users', $data, 'id = :id', ['id' => $id]);
            header('Location: ?o=Users&m=read&id=' . $id . '&t=' . $this->ctx->in['t']);
            exit();
        }
        return $this->dbh->read('users', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }

    #[\Override]
    public function delete(): array
    {
        $id = filter_var($this->in['id'], FILTER_VALIDATE_INT);
        $this->dbh->delete('users', 'id = :id', ['id' => $id]);
        header('Location: ?o=Users&t=' . $this->ctx->in['t']);
        exit();
    }

    #[\Override]
    public function list(): array
    {
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $perPage = filter_var($_REQUEST['perpage'] ?? self::DEFAULT_PER_PAGE, FILTER_VALIDATE_INT)
        ?: self::DEFAULT_PER_PAGE;
        $offset = ($page - 1) * $perPage;
        $searchQuery = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];

        if ($searchQuery !== '') {
            $where = '(login LIKE :search OR fname LIKE :search OR lname LIKE :search)';
            $params['search'] = '%' . $searchQuery . '%';
        }

        $total = $this->dbh->read('users', 'COUNT(*)', $where, $params, QueryType::Col);
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        return [
            'items' => $this->dbh->read(
                'users',
                '*',
                $where . ' ORDER BY updated DESC, created DESC LIMIT :limit OFFSET :offset',
                $params,
                QueryType::All,
            ),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage),
            ],
        ];
    }
}
