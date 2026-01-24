<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Session\Core;

class View
{
    public function __construct(protected Ctx $ctx, protected array $ary) {}
    public function list(): string { return "<div class=\"card\"><h2>{$this->ary['head']}</h2><p>{$this->ary['main']}</p></div>"; }
}
