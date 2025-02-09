<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250209
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

require_once __DIR__ . '/../../vendor/autoload.php';

// Use namespaced classes
use SPE\Autoload\Core\Init;
use SPE\Autoload\Core\Cfg;
use SPE\Autoload\Core\Ctx;

define('DBG', true);

// Bootstrap the application
echo new Init(new Cfg(), new Ctx());
