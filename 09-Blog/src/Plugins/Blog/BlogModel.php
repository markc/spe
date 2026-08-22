<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Plugins\Blog;

use SPE\Blog\Core\{Content, Type};

final class BlogModel extends Content
{
    #[\Override]
    protected function type(): Type
    {
        return Type::Post;
    }
}
