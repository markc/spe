# SPE::09 Blog

_Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)_

Chapter 09 is the destination the whole series has been walking toward: a small but real content engine, built entirely from the pieces the earlier chapters introduced. One table holds several kinds of content, distinguished by a `Type` enum; bodies are written in Markdown and rendered safely; a `Post` object exposes derived values as property hooks; entries carry tags through a junction table; lists paginate and filter; and a Docs section is served by the very engine that documents it. It is the concrete answer to the question the project set out to pose — *for a moderately complex site, do you actually need Laravel?* — and the answer, in a few hundred lines of plain PHP 8.5, is no.

## The one idea

**The application.** Blog and Docs are the same code with a different `Type`; Markdown, tags, pagination and a documentation site fall out of that one design.

## What's on the screen

A **Blog** of Markdown posts with tags and pagination; a single-post view with prev/next and oldest/newest links; a **Docs** section (the same engine showing `type = doc`); an admin-only **Tags** manager; and, carried from chapter 08, Auth and Users. Sign in as admin to write. Run `php 09-Blog/bin/seed-docs.php` to import this tutorial's chapter READMEs as docs.

## Walkthrough

### Type — one table, several kinds

```php
enum Type: string
{
    case Post = 'post';
    case Doc = 'doc';
    public function label(): string { … }
    public function icon(): string { … }
}
```

The `posts` table gains a `type` column. Rather than a separate table and plugin per kind of content, one enum says which kind a row is, and one body of code handles them all.

### Content — the shared CRUDL

`Content` is an abstract plugin holding the entire list/read/create/update/delete implementation for content of one type. A concrete plugin says only *which* type:

```php
final class BlogModel extends Content { protected function type(): Type { return Type::Post; } }
final class DocsModel extends Content { protected function type(): Type { return Type::Doc; } }
```

That is the payoff of the series made literal: Blog and Docs are two lines each because everything else is shared. `Content::guard()` keeps reads public and writes admin-only; every query is scoped to `type()`, so a post id is not reachable through the Docs plugin and vice versa.

### Md — Markdown as a safe pipe

```php
public static function render(string $markdown): string
{
    return $markdown
        |> (static fn(string $s) => str_replace("\r\n", "\n", $s))
        |> htmlspecialchars(...)
        |> self::codeBlocks(...)
        |> self::blocks(...);
}
```

The renderer is a **pipe of pure steps**, and the very first transform after normalising newlines is `htmlspecialchars` — the input is escaped *before* any Markdown is interpreted, so anything the renderer does not deliberately turn into a tag appears as literal text. Link and image targets are the one place a URL could smuggle in a script, so they are checked with the **URI extension**:

```php
try { $scheme = new Uri($url)->getScheme(); }
catch (\Throwable) { return null; }
if ($scheme !== null && !in_array(strtolower($scheme), self::SCHEMES, true)) return null;
```

Only `http`, `https`, `mailto` and relative URLs survive; `javascript:` and anything malformed are dropped, leaving just the link text. This is why the tests can post `<script>` and a `javascript:` link and find neither in the output.

### Post — property hooks

```php
final class Post
{
    public string $html    { get => Md::render($this->body); }
    public string $excerpt { get => /* first 160 chars of the stripped body */; }
    public function __construct(public int $id, public Type $type, public string $title, …) {}
}
```

`html` and `excerpt` are **property hooks** (PHP 8.4): they read like plain properties but run code on access. The rendered HTML and the list excerpt are therefore always derived from the current body — there is nothing stored to fall out of date. In the view, `{$post->html}` is the one value printed *without* `e()`, and it is safe precisely because `Md` produced it; the name `html` marks that exception deliberately.

### Tags, pagination, and neighbours

Tags are many-to-many (`tags` + `post_tags`). `Content::list()` paginates (`LIMIT`/`OFFSET` with a page count) and, when a `tag` slug is supplied, joins through the junction to filter. A single post's neighbours use **`array_first()` and `array_last()`** (PHP 8.4) over the ordered list of sibling ids to offer oldest/newest links alongside prev/next. `Ctx` gains two validated inputs for this — `page` (a positive int) and `tag` (a slug pattern) — added to the request contract for this chapter. `TagsModel` is an admin-only CRUDL that also shows each tag's post count.

### Docs that document the engine

`bin/seed-docs.php` reads every `docs/0*-*/README.md`, takes the first heading as the title and the chapter folder as the slug, and stores each as a `type = doc` row (using `(void) $db->create(...)` to acknowledge the ignored id that `#[\NoDiscard]` would otherwise warn about). The finished application then serves this tutorial through the same `Content` code that serves the blog.

## PHP features introduced

- **Property hooks (8.4)** — `Post::$html` and `$excerpt` are computed on read, never stored stale.
- **`array_first()` / `array_last()` (8.4)** — the oldest/newest neighbours of a post.
- **The URI extension** — robust scheme extraction to sanitise Markdown links.
- **The pipe operator as a render pipeline** — `Md::render` reads as the sequence of transforms it performs.
- **Abstract base sharing** — `Content` and `ContentView` give Blog and Docs one implementation.

## Security

The chapter's new surface is user-authored Markdown, and it is handled to the series' standard: the body is escaped before rendering, so stored `<script>` shows as text; link and image schemes are validated against an allow-list, so `javascript:` URLs are dropped; and slugs are generated by the application, never taken from input. Everything from chapter 08 still holds — writes are admin-only and CSRF-checked, reads are public, queries are prepared and type-scoped. The single unescaped value, `$post->html`, is output the application itself produced.

## Try it

```bash
# From the repo root, so the shared base.css/site.css/base.js load:
php -S localhost:8000 index.php   # then open http://localhost:8000/09-Blog/
php 09-Blog/bin/seed-docs.php     # optional: serve this tutorial as Docs
```

Read the Blog, page through it, click a tag to filter, open Docs, and sign in as admin (`admin@example.com` / `admin`) to write a post in Markdown with tags. `?o=Blog&x=json` returns the list as data.

## The series, complete

Nine chapters, each one idea: a request becomes a page (01); presentation is shared and frozen (02); the request contract and plugins (03); model/view/theme and escape-at-output (04); PSR-4 and Composer (05); sessions, flash and CSRF (06); PDO and prepared statements (07); users, roles and login (08); and a Markdown content engine with tags, pagination and self-hosted docs (09). No framework, no magic — just modern PHP used deliberately. That is the point.
