<?php declare(strict_types=1);
// Created: 20150101 - Updated: 20251209
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

require_once __DIR__ . '/../../vendor/autoload.php';

use SPE\Autoload\Core\{Init, Ctx};

echo new Init(new Ctx);
