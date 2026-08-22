<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

use Tests\Support\Chapter;

/** The running server for a chapter directory, started on first use. */
function chapter(string $dir): Chapter
{
    return Chapter::start($dir);
}

register_shutdown_function(Chapter::stopAll(...));
