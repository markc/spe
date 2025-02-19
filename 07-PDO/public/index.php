<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\PDO\Core\Init;
use SPE\PDO\Core\Ctx;

define('DBG', true);

// Bootstrap the application
echo new Init(new Ctx());
