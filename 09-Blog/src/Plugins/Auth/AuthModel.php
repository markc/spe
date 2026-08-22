<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Auth;

use SPE\Blog\Core\{Flash, Plugin, QueryType, User};

/** A session is a resource: create() logs in, delete() logs out. */
final class AuthModel extends Plugin
{
    #[\Override]
    public function create(): array
    {
        if ($this->ctx->user) {
            $this->redirect('?o=Home');
        }
        if ($p = $this->ctx->post()) {
            $email = trim((string) ($p['email'] ?? ''));
            $row = $this->ctx->db->read('users', '*', 'email = :e', ['e' => $email], QueryType::One);
            if ($row && password_verify((string) ($p['password'] ?? ''), $row['password'])) {
                $this->ctx->login(User::fromRow($row), !empty($p['remember']));
                $this->ctx->flash(Flash::Success, "Welcome back, {$row['name']}.");
                $this->redirect('?o=Home');
            }
            // The same message whether the email or the password was wrong.
            $this->ctx->flash(Flash::Danger, 'Those credentials did not match.');
            $this->redirect('?o=Auth&m=create');
        }
        return ['title' => 'Sign in'];
    }

    #[\Override]
    public function delete(): array
    {
        if ($this->ctx->post()) {
            $this->ctx->logout();
            $this->ctx->flash(Flash::Success, 'Signed out.');
        }
        $this->redirect('?o=Home');
    }
}
