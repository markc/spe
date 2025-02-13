<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250213
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\BareBone\Core\Init;
use SPE\BareBone\Core\Ctx;

define('DBG', true);

echo new Init(new Ctx());
