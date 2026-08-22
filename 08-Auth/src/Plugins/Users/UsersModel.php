<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Auth\Plugins\Users;

use SPE\Auth\Core\{Flash, Plugin, QueryType, Role};

/** Managing users is admin-only, every method. */
final class UsersModel extends Plugin
{
    #[\Override]
    public static function guard(string $m): Role
    {
        return Role::Admin;
    }

    #[\Override]
    public function list(): array
    {
        return ['title' => 'Users', 'items' => $this->ctx->db->read('users', 'id, email, name, role, created', order: 'ORDER BY id')];
    }

    #[\Override]
    public function read(): array
    {
        return $this->find($this->ctx->in['i']) ?: ['title' => 'Not found', 'body' => 'There is no such user.'];
    }

    #[\Override]
    public function create(): array
    {
        if ($p = $this->ctx->post()) {
            $fields = $this->fields($p);
            if (($p['password'] ?? '') === '') {
                $this->ctx->flash(Flash::Warning, 'A new user needs a password.');
                $this->redirect('?o=Users&m=create');
            }
            $fields['password'] = password_hash((string) $p['password'], PASSWORD_DEFAULT);
            $id = $this->ctx->db->create('users', $fields);
            $this->ctx->flash(Flash::Success, 'User created.');
            $this->redirect("?o=Users&m=read&i=$id");
        }
        return ['title' => 'New user', 'user' => ['email' => '', 'name' => '', 'role' => 'User'], 'action' => '?o=Users&m=create', 'roles' => $this->roles()];
    }

    #[\Override]
    public function update(): array
    {
        $user = $this->find($this->ctx->in['i']);
        if (!$user) {
            http_response_code(404);
            return ['title' => 'Not found', 'body' => 'There is no such user.'];
        }
        if ($p = $this->ctx->post()) {
            $fields = $this->fields($p);
            if (($p['password'] ?? '') !== '') {
                $fields['password'] = password_hash((string) $p['password'], PASSWORD_DEFAULT);
            }
            (void) $this->ctx->db->update('users', $fields, 'id = :id', ['id' => $user['id']]);
            $this->ctx->flash(Flash::Success, 'User updated.');
            $this->redirect("?o=Users&m=read&i={$user['id']}");
        }
        return ['title' => "Edit: {$user['name']}", 'user' => $user, 'action' => "?o=Users&m=update&i={$user['id']}", 'roles' => $this->roles()];
    }

    #[\Override]
    public function delete(): array
    {
        if ($this->ctx->post()) {
            if ($this->ctx->in['i'] === $this->ctx->user?->id) {
                $this->ctx->flash(Flash::Warning, 'You cannot delete your own account.');
            } else {
                $this->ctx->db->delete('users', 'id = :id', ['id' => $this->ctx->in['i']])
                    ? $this->ctx->flash(Flash::Success, 'User deleted.')
                    : $this->ctx->flash(Flash::Warning, 'No such user.');
            }
        }
        $this->redirect('?o=Users');
    }

    private function find(int $id): array|false
    {
        return $this->ctx->db->read('users', 'id, email, name, role, created', 'id = :id', ['id' => $id], QueryType::One);
    }

    /** @return array{email:string,name:string,role:string} */
    private function fields(array $p): array
    {
        $role = Role::tryFrom((string) ($p['role'] ?? '')) ?? Role::User;
        return [
            'email' => trim((string) ($p['email'] ?? '')),
            'name' => trim((string) ($p['name'] ?? '')) ?: 'Unnamed',
            'role' => $role->value,
        ];
    }

    /** @return list<string> */
    private function roles(): array
    {
        return array_map(static fn(Role $r) => $r->value, [Role::User, Role::Admin]);
    }
}
