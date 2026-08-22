<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\PDO\Core;

/** The shape a read should return: every row, one row, or one scalar. */
enum QueryType
{
    case All;
    case One;
    case Col;
}
