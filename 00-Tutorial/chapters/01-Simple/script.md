# 01-Simple — pilot screencast script (~3 min)

Pilot for the new format: **narration** + **shot** directions across three lanes
(Browser = Playwright, Code = editor, Terminal). Line anchors are real
(`01-Simple/public/index.php`). Acronyms stay plain in the text; pronunciation is
handled by the lexicon, not spelled out here.

| # | Shot | Narration |
|---|------|-----------|
| 1 | **Browser** — the running page at `/01-Simple/`, click Home → About → Contact | "This is a complete PHP web page — navigation, three pages, a 404 — and it's produced by a single PHP statement. No framework, no config. Let's see how." |
| 2 | **Code** — whole file in view (`index.php:1-57`), then zoom to line 4 | "The whole program is `echo new class`. PHP builds an anonymous object and prints it — which triggers its `__toString`. So the object's only job is to know which page was asked for, and render itself." |
| 3 | **Code** — highlight the `PAGES` constant (`:7-11`) | "Pages live in one typed constant — a name mapped to a title and its HTML. It's fixed at parse time and shared by every request." |
| 4 | **Code** — highlight `public private(set)` (`:13-14`) | "These two properties use asymmetric visibility, new in 8.4: anyone can read them, only this class can set them. That's exactly what you want for a value computed once from the request — no getter boilerplate." |
| 5 | **Code** — zoom the pipe in the constructor (`:18-22`); **Browser** — show `?o=%20ABOUT%20` resolving to About | "Here's the 8.5 pipe operator. Read it top to bottom: take the query parameter, trim it, lower-case it, and fall back to 'home' if it's empty. Same logic as nesting the calls — but in the order they actually happen." |
| 6 | **Code** — highlight the 404 branch (`:24-27`); **Browser** — visit `?o=nope`, show the Not-found page and the 404 in devtools Network | "An unknown page is a real 404, not a 200 that says 'not found'. The status line tells the truth — which matters for browsers, crawlers, and the tests." |
| 7 | **Code** — `__toString` nav pipe + heredoc (`:30-55`) | "Rendering is another pipe — page keys become links, the current one marked active — dropped into a heredoc. The HTML reads as HTML: no concatenation, no template engine." |
| 8 | **Browser** — try `?o=<script>alert(1)</script>`, show a clean 404, no alert | "One security habit from line one: the input is only ever used as an array key, never printed back. So this injection attempt is just an unknown page — a clean 404." |
| 9 | **Browser** — back to Home | "That's the whole engine in 57 lines. Every later chapter adds exactly one idea to this. Next: chapter 2 gives it a real look — shared CSS, dark mode, an app shell — without changing this logic at all." |

**Shot notes**
- Record the browser at 1920×1080, `deviceScaleFactor: 2`; zoom by cropping, never scaling up.
- Keep the editor on a large font (24–26px) — code legibility is the whole point.
- Target ~3:00. Narrate per sentence so any line is a one-take re-render.
