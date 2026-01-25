<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

enum QueryType: string
{
    case All = 'all';
    case One = 'one';
    case Col = 'col';
}
