<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250209
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

require_once __DIR__ . '/../../vendor/autoload.php';  // Ensure vendor exists in parent dir

use SPE\PDO\Core\Init;
use SPE\PDO\Core\Cfg;
use SPE\PDO\Core\Ctx;

define('DBG', true);

echo new Init(new Cfg(), new Ctx());
