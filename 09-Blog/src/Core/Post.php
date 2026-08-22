<?php declare(strict_types=1);
// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\Blog\Core;

/**
 * A content record. `html` and `excerpt` are property hooks: derived values that
 * read like plain properties but are computed on access, so nothing stale is stored.
 */
final class Post
{
    public string $html {
        get => Md::render($this->body);
    }

    public string $excerpt {
        get {
            $plain = Md::render($this->body)
                |> strip_tags(...)
                |> (static fn(string $s) => html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                |> (static fn(string $s) => trim((string) preg_replace('/\s+/', ' ', $s)));
            return mb_strlen($plain) > 160 ? rtrim(mb_substr($plain, 0, 160)) . '…' : $plain;
        }
    }

    /** @param list<array{id:int,name:string,slug:string}> $tags */
    public function __construct(
        public int $id,
        public Type $type,
        public string $title,
        public string $slug,
        public string $body,
        public string $created,
        public array $tags = [],
    ) {}

    /** @param array{id:int|string,type:string,title:string,slug:string,body:string,created:string} $row */
    public static function fromRow(array $row, array $tags = []): self
    {
        return new self((int) $row['id'], Type::from($row['type']), $row['title'], $row['slug'], $row['body'], $row['created'], $tags);
    }
}
