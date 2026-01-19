<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Users\Plugins\Users;

use SPE\App\Db;
use SPE\App\QueryType;
use SPE\App\Util;
use SPE\Users\Core\Ctx;

final class UsersModel
{
    private Db $db;
    private array $f = [
        'i' => 0,
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
        private Ctx $ctx,
    ) {
        foreach ($this->f as $k => &$v)
            $v = $_REQUEST[$k] ?? $v;
        $this->db = new Db('users');
    }

    // Auth: login form and processing
    public function login(): array
    {
        if (Util::is_usr()) {
            header('Location: /');
            exit();
        }

        if (!Util::is_post()) {
            return ['action' => 'login'];
        }

        $login = trim($_POST['login'] ?? '');
        $webpw = $_POST['webpw'] ?? '';

        if (!$login || !$webpw) {
            Util::log('Please enter email and password');
            return ['action' => 'login', 'login' => $login];
        }

        $usr = $this->db->read('users', '*', 'login = :login', ['login' => $login], QueryType::One);

        if (!$usr || !password_verify($webpw, $usr['webpw'])) {
            Util::log('Invalid email or password');
            return ['action' => 'login', 'login' => $login];
        }

        if ((int) $usr['acl'] === 9) {
            Util::log('Account is disabled');
            return ['action' => 'login', 'login' => $login];
        }

        $_SESSION['usr'] = [
            'id' => $usr['id'],
            'login' => $usr['login'],
            'fname' => $usr['fname'],
            'lname' => $usr['lname'],
            'acl' => $usr['acl'],
        ];

        // Set "remember me" cookie if requested
        if (!empty($_POST['remember'])) {
            Util::setRemember($this->db, (int) $usr['id']);
        }

        Util::log(($usr['fname'] ?: $usr['login']) . ' logged in', 'success');
        header('Location: /');
        exit();
    }

    // Auth: logout
    public function logout(): array
    {
        if (Util::is_usr()) {
            // Clear remember cookie
            Util::clearRemember($this->db, (int) $_SESSION['usr']['id']);
            unset($_SESSION['usr']);
            session_regenerate_id(true);
            Util::log('Logged out', 'success');
        }
        header('Location: /');
        exit();
    }

    // Auth: user profile
    public function profile(): array
    {
        $id = (int) $_SESSION['usr']['id'];
        if (Util::is_post()) {
            $data = [
                'fname' => trim($_POST['fname'] ?? ''),
                'lname' => trim($_POST['lname'] ?? ''),
                'altemail' => trim($_POST['altemail'] ?? ''),
                'updated' => date('Y-m-d H:i:s'),
            ];
            if (!empty($_POST['webpw'])) {
                $data['webpw'] = password_hash($_POST['webpw'], PASSWORD_DEFAULT);
            }
            $this->db->update('users', $data, 'id = :id', ['id' => $id]);

            // Update session
            $_SESSION['usr']['fname'] = $data['fname'];
            $_SESSION['usr']['lname'] = $data['lname'];

            Util::log('Profile updated', 'success');
            header('Location: ?o=Users&m=profile');
            exit();
        }
        return $this->db->read('users', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }

    // Admin CRUD methods
    public function create(): array
    {
        if (Util::is_post()) {
            $this->db->create('users', [
                'grp' => (int) $this->f['grp'],
                'acl' => (int) $this->f['acl'],
                'login' => $this->f['login'],
                'fname' => $this->f['fname'],
                'lname' => $this->f['lname'],
                'altemail' => $this->f['altemail'],
                'webpw' => $this->f['webpw'] ? password_hash($this->f['webpw'], PASSWORD_DEFAULT) : '',
                'otp' => $this->f['otp'],
                'otpttl' => (int) $this->f['otpttl'],
                'cookie' => $this->f['cookie'],
                'anote' => $this->f['anote'],
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
            ]);
            Util::log('User created successfully', 'success');
            header('Location: ?o=Users');
            exit();
        }
        return [];
    }

    public function read(): array
    {
        return $this->db->read('users', '*', 'id = :id', ['id' => (int) $this->f['i']], QueryType::One) ?: [];
    }

    public function update(): array
    {
        $id = (int) $this->f['i'];
        if (Util::is_post()) {
            $data = [
                'grp' => (int) $this->f['grp'],
                'acl' => (int) $this->f['acl'],
                'login' => $this->f['login'],
                'fname' => $this->f['fname'],
                'lname' => $this->f['lname'],
                'altemail' => $this->f['altemail'],
                'otp' => $this->f['otp'],
                'otpttl' => (int) $this->f['otpttl'],
                'cookie' => $this->f['cookie'],
                'anote' => $this->f['anote'],
                'updated' => date('Y-m-d H:i:s'),
            ];
            if ($this->f['webpw'])
                $data['webpw'] = password_hash($this->f['webpw'], PASSWORD_DEFAULT);
            $this->db->update('users', $data, 'id = :id', ['id' => $id]);
            Util::log('User updated successfully', 'success');
            header("Location: ?o=Users&m=read&i=$id");
            exit();
        }
        return $this->db->read('users', '*', 'id = :id', ['id' => $id], QueryType::One) ?: [];
    }

    public function delete(): array
    {
        $this->db->delete('users', 'id = :id', ['id' => (int) $this->f['i']]);
        Util::log('User deleted', 'success');
        header('Location: ?o=Users');
        exit();
    }

    public function list(): array
    {
        $page = (int) ($_REQUEST['page'] ?? 1) ?: 1;
        $pp = $this->ctx->perp;
        $q = trim($_GET['q'] ?? '');

        [$where, $params] = $q ? ['(login LIKE :s OR fname LIKE :s OR lname LIKE :s)', ['s' => "%$q%"]] : ['1=1', []];
        $total = $this->db->read('users', 'COUNT(*)', $where, $params, QueryType::Col);

        return [
            'items' => $this->db->read('users', '*', "$where ORDER BY updated DESC LIMIT :l OFFSET :o", [
                ...$params,
                'l' => $pp,
                'o' => ($page - 1) * $pp,
            ]),
            'pagination' => ['page' => $page, 'perPage' => $pp, 'total' => $total, 'pages' => (int) ceil($total / $pp)],
        ];
    }
}
