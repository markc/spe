<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Autoload\Core;

final class Util {
    public static function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
