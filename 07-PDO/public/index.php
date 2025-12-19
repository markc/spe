<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

require_once __DIR__ . '/../../vendor/autoload.php';
use SPE\App\Env;
Env::load(__DIR__ . '/..');
echo new SPE\PDO\Core\Init(new SPE\PDO\Core\Ctx);
