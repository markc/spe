<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Auth;

use SPE\Blog\Core\View;

final class AuthView extends View
{
    #[\Override]
    public function create(): string
    {
        return <<<HTML
<div class="card" style="max-width:24rem;margin-inline:auto">
    <h2>Sign in</h2>
    <form method="post" action="?o=Auth&m=create">
        {$this->csrf()}
        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="admin@example.com" required></div>
        <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" value="admin" required></div>
        <label class="checkbox-label"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <div class="btn-group mt-2"><button type="submit" class="btn">Sign in</button></div>
    </form>
</div>
HTML;
    }
}
