<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\App\Env;
use SPE\HCP\Core\{Init, Ctx};

Env::load(__DIR__ . '/../.env');
echo new Init(new Ctx);
