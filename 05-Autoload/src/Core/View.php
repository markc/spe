<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

class View
{
    public function __construct(protected Ctx $ctx, protected array $data) {}

    public function create(): string { return $this->card(); }
    public function read(): string { return $this->card(); }
    public function update(): string { return $this->card(); }
    public function delete(): string { return $this->card(); }
    public function list(): string { return $this->card(); }

    protected function card(): string
    {
        return <<<HTML
<div class="card"><h2>{$this->e($this->data['title'])}</h2><p>{$this->e($this->data['body'])}</p></div>
HTML;
    }

    protected function e(string|int|float|null $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
