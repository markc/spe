<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Users;

use SPE\App\{Db, QueryType};
use SPE\Users\Core\{Ctx, Plugin};

final class UsersModel extends Plugin {
    private const int PER_PAGE = 10;
    private ?Db $dbh = null;
    private array $in = [
        'id' => 0, 'grp' => 0, 'acl' => 0, 'login' => '', 'fname' => '', 'lname' => '',
        'altemail' => '', 'webpw' => '', 'otp' => '', 'otpttl' => 0, 'cookie' => '', 'anote' => ''
    ];

    public function __construct(protected Ctx $ctx) {
        parent::__construct($ctx);
        foreach ($this->in as $k => &$v) $v = $_REQUEST[$k] ?? $v;
        $this->dbh ??= new Db('users');
    }

    #[\Override] public function create(): array {
        if ($_POST) {
            $this->dbh->create('users', [
                'grp' => (int)$this->in['grp'], 'acl' => (int)$this->in['acl'],
                'login' => $this->in['login'], 'fname' => $this->in['fname'], 'lname' => $this->in['lname'],
                'altemail' => $this->in['altemail'],
                'webpw' => $this->in['webpw'] ? password_hash($this->in['webpw'], PASSWORD_DEFAULT) : '',
                'otp' => $this->in['otp'], 'otpttl' => (int)$this->in['otpttl'],
                'cookie' => $this->in['cookie'], 'anote' => $this->in['anote'],
                'created' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')
            ]);
            header('Location: ?o=Users&t=' . $this->ctx->in['t']);
            exit;
        }
        return [];
    }

    #[\Override] public function read(): array {
        return $this->dbh->read('users', '*', 'id = :id', ['id' => (int)$this->in['id']], QueryType::One) ?: [];
    }

    #[\Override] public function update(): array {
        $id = (int)$this->in['id'];
        if ($_POST) {
            $data = [
                'grp' => (int)$this->in['grp'], 'acl' => (int)$this->in['acl'],
                'login' => $this->in['login'], 'fname' => $this->in['fname'], 'lname' => $this->in['lname'],
                'altemail' => $this->in['altemail'], 'otp' => $this->in['otp'], 'otpttl' => (int)$this->in['otpttl'],
                'cookie' => $this->in['cookie'], 'anote' => $this->in['anote'], 'updated' => date('Y-m-d H:i:s')
            ];
            if ($this->in['webpw']) $data['webpw'] = password_hash($this->in['webpw'], PASSWORD_DEFAULT);
            $this->dbh->update('users', $data, 'id = :id', ['id' => $id]);
            header("Location: ?o=Users&m=read&id=$id&t=" . $this->ctx->in['t']);
            exit;
        }
        return $this->dbh->read('users', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }

    #[\Override] public function delete(): array {
        $this->dbh->delete('users', 'id = :id', ['id' => (int)$this->in['id']]);
        header('Location: ?o=Users&t=' . $this->ctx->in['t']);
        exit;
    }

    #[\Override] public function list(): array {
        $page = filter_var($_REQUEST['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $pp = filter_var($_REQUEST['perpage'] ?? self::PER_PAGE, FILTER_VALIDATE_INT) ?: self::PER_PAGE;
        $q = trim($_GET['q'] ?? '');

        [$where, $params] = $q ? ['(login LIKE :s OR fname LIKE :s OR lname LIKE :s)', ['s' => "%$q%"]] : ['1=1', []];
        $total = $this->dbh->read('users', 'COUNT(*)', $where, $params, QueryType::Col);

        return [
            'items' => $this->dbh->read('users', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o", [...$params, 'l' => $pp, 'o' => ($page - 1) * $pp]),
            'pagination' => ['page' => $page, 'perPage' => $pp, 'total' => $total, 'pages' => ceil($total / $pp)]
        ];
    }
}
