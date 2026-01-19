<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\Autoload\Core\{Init, Ctx};

echo new Init(new Ctx);
