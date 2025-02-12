<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250212
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Auth\Plugins\Auth;

use SPE\Auth\Core\{Ctx, Util};

class View
{
    public function __construct(private Ctx $ctx)
    {
    }

    public function create(array $in): string
    {
        Util::elog(__METHOD__);

        $login = $in['login'] ?? '';
        return <<<HTML
        <div class="row justify-content-center">
          <div class="col-md-4">
            <div class="card shadow">
              <div class="card-body">
                <h1 class="mb-4"><i class="bi bi-person-plus"></i> Sign up</h1>
                <form action="{$this->ctx->self}" method="post">
                  <input type="hidden" name="c" value="{$_SESSION['c']}">
                  <input type="hidden" name="plugin" value="{$this->ctx->in['o']}">
                  <div class="mb-3">
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input type="email" name="login" id="login" class="form-control" placeholder="Your Email Address" value="{$login}" autofocus required>
                    </div>
                  </div>
                  <div class="mb-3">
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-key"></i></span>
                      <input type="password" name="passwd1" id="passwd1" class="form-control" placeholder="Choose Password" required>
                    </div>
                  </div>
                  <div class="mb-4">
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-key"></i></span>
                      <input type="password" name="passwd2" id="passwd2" class="form-control" placeholder="Confirm Password" required>
                    </div>
                  </div>
                  <div class="d-flex justify-content-end">
                    <div class="btn-group">
                      <a class="btn btn-outline-primary" href="?o=Auth&m=read">&laquo; Back to Sign in</a>
                      <button class="btn btn-primary" type="submit" name="action" value="create">Sign up</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        HTML;
    }

    public function read(array $in): string
    {
        Util::elog(__METHOD__);

        $login = $in['login'] ?? '';
        return <<<HTML
        <div class="row justify-content-center">
          <div class="col-md-4">
            <div class="card shadow">
              <div class="card-body">
                <h1 class="mb-4"><i class="bi bi-key"></i> Sign in</h1>
                <form action="{$this->ctx->self}" method="post">
                  <input type="hidden" name="c" value="{$_SESSION['c']}">
                  <input type="hidden" name="plugin" value="{$this->ctx->in['o']}">
                  <div class="mb-3">
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input type="email" class="form-control" name="login" id="login" placeholder="Your Email Address" value="{$login}" required>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="visually-hidden" for="webpw">Password</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-key"></i></span>
                      <input type="password" name="webpw" id="webpw" class="form-control" placeholder="Your Password" autocomplete="current-password" required>
                    </div>
                  </div>
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="" name="remember" id="remember">
                    <label class="form-check-label" for="remember">
                      Remember me on this computer
                    </label>
                  </div>
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex justify-content-end">
                      <div class="btn-group">
                        <a class="btn btn-outline-primary" href="?o=Auth&m=create">Sign up</a>
                        <button class="btn btn-primary" type="submit" id="action" name="action" value="read">Sign in</button>
                      </div>
                    </div>
                    <div class="text-end">
                      <a href="?o=Auth&m=list" class="text-decoration-none">Forgot password?</a>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        HTML;
    }

    public function update(array $in): string
    {
        Util::elog(__METHOD__);

        $id = $in['id'] ?? '';
        $login = $in['login'] ?? '';
        return <<<HTML
        <div class="row justify-content-center">
          <div class="col-md-6 col-lg-5 col-xl-4">
            <h3 class="mb-4"><i class="bi bi-key"></i> Update Password</h3>
            <form action="{$this->ctx->self}" method="post">
              <input type="hidden" name="c" value="{$_SESSION['c']}">
              <input type="hidden" name="plugin" value="{$this->ctx->in['o']}">
              <input type="hidden" name="id" value="{$id}">
              <input type="hidden" name="login" value="{$login}">
              <p class="text-center mb-4"><strong>For {$login}</strong></p>
              <div class="mb-3">
                <label class="visually-hidden" for="passwd1">New Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-key"></i></span>
                  <input class="form-control" type="password" name="passwd1" id="passwd1" placeholder="New Password" required>
                </div>
              </div>
              <div class="mb-4">
                <label class="visually-hidden" for="passwd2">Confirm Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-key"></i></span>
                  <input class="form-control" type="password" name="passwd2" id="passwd2" placeholder="Confirm Password" required>
                </div>
              </div>
              <div class="d-flex justify-content-end">
                <button class="btn btn-primary" type="submit" name="action" value="update">Update my password</button>
              </div>
            </form>
          </div>
        </div>
        HTML;
    }

    public function delete(array $in): string
    {
        Util::elog(__METHOD__);

        $id = $in['id'] ?? '';
        $login = $in['login'] ?? '';
        return <<<HTML
        <div class="row justify-content-center">
          <div class="col-md-4">
            <div class="card shadow">
              <div class="card-body">
                <h1 class="mb-4"><i class="bi bi-exclamation-triangle"></i> Delete Account</h1>
                <form action="{$this->ctx->self}" method="post">
                  <input type="hidden" name="c" value="{$_SESSION['c']}">
                  <input type="hidden" name="plugin" value="{$this->ctx->in['o']}">
                  <input type="hidden" name="id" value="{$id}">
                  <input type="hidden" name="login" value="{$login}">
                  <p class="text-center mb-4">Are you sure you want to delete the account for <strong>{$login}</strong>?</p>
                  <div class="d-flex justify-content-end">
                    <div class="btn-group">
                      <a class="btn btn-outline-primary" href="?o=Auth&m=read">Cancel</a>
                      <button class="btn btn-danger" type="submit" name="action" value="delete">Delete Account</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        HTML;
    }

    public function list(array $in = []): string
    {
        Util::elog(__METHOD__);

        $login = $in['login'] ?? '';
        return <<<HTML
        <div class="row justify-content-center">
          <div class="col-md-4">
            <div class="card shadow">
              <div class="card-body">
                <h1 class="mb-4"><i class="bi bi-key"></i> Forgot password</h1>
                <form action="{$this->ctx->self}" method="post">
                  <input type="hidden" name="c" value="{$_SESSION['c']}">
                  <input type="hidden" name="plugin" value="{$this->ctx->in['o']}">
                  <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="login" id="login" class="form-control" placeholder="Your Login Email Address" value="{$login}" autofocus required>
                  </div>
                  <div class="mb-4">
                    <small class="text-muted d-block text-center">
                      You will receive an email with further instructions and please note that this only resets the password for this website interface.
                    </small>
                  </div>
                  <div class="d-flex justify-content-end">
                    <div class="btn-group">
                      <a class="btn btn-outline-primary" href="?o=Auth&m=read">&laquo; Back to Sign in</a>
                      <button class="btn btn-primary" type="submit" name="action" value="list">Send</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        HTML;
    }
}
