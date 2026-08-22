# 01-Simple — pilot screencast script (~3–4 min)

Pilot for the new format: **narration** + **shot** directions across three lanes
(Browser = Playwright, Code = editor, Terminal). Line anchors are real
(`01-Simple/public/index.php`). Voice: **Chirp 3 HD** (`en-US-Chirp3-HD-Charon`).
Sentences are deliberately long and flowing — Chirp reads with better metre when
it isn't broken into short clauses.

| # | Shot | Narration |
|---|------|-----------|
| 1 | **Browser** — the running page at `/01-Simple/`, slowly click Home → About → Contact | "What you're looking at is a complete PHP web page, with working navigation across three pages and a real not-found response, and the whole thing is produced by a single PHP statement with no framework and no configuration behind it, so let's walk through exactly how that works." |
| 2 | **Code** — whole file (`index.php:1-57`), then zoom to line 4 | "The entire program is a single expression: echo, followed by a new anonymous class, so PHP builds the object and immediately prints it, and printing an object is what triggers its toString method, which means the object really has only one job — to work out which page was requested and then render itself as HTML." |
| 3 | **Code** — highlight the `PAGES` constant (`:7-11`) | "All of the pages live in one typed constant that maps each name to its title and the markup that goes with it, and because it's a constant rather than a property it's fixed when the file is parsed and shared across every request instead of being rebuilt each time the page loads." |
| 4 | **Code** — highlight `public private(set)` (`:13-14`) | "These two properties use asymmetric visibility, which arrived in PHP 8.4, so anyone can read them but only the class itself is allowed to set them, and that is exactly the guarantee you want for a value that's computed once from the request and should never be quietly changed anywhere else afterwards." |
| 5 | **Code** — zoom the pipe (`:18-22`); **Browser** — show `?o=%20ABOUT%20` resolving to About | "Here is the PHP 8.5 pipe operator handling the request, and you can read it straight down the page: take the query parameter, trim the whitespace, lower-case it, and fall back to the home page when nothing is left, which is the very same logic you would otherwise write as awkward nested function calls, except that now it reads in the exact order the steps actually happen." |
| 6 | **Code** — the 404 branch (`:24-27`); **Browser** — visit `?o=nope`, show the page and the 404 in devtools Network | "When the requested page isn't one that we recognise, the code returns a genuine 404 status rather than a 200 response that merely says not found, because that status line is what browsers, search crawlers and the test suite all rely on to decide whether the page truly exists." |
| 7 | **Code** — `__toString` nav pipe + heredoc (`:30-55`) | "Rendering the page is just another pipe, where the page names become links and the current one is marked active, and the whole result is dropped into a heredoc template, so the HTML is written plainly as HTML with no string concatenation and no separate templating engine that you would have to learn on the side." |
| 8 | **Browser** — try `?o=<script>alert(1)</script>`, show a clean 404, no alert | "And this is the one security habit worth carrying with you from the very first chapter: the incoming value is only ever used to look a page up, and it is never printed back into the document, so even a deliberate script-injection attempt simply becomes an unknown page and quietly returns a clean 404." |
| 9 | **Browser** — back to Home | "That is the entire engine in just fifty-seven lines, and every chapter from here builds on it by adding exactly one new idea, beginning with the next one, where we give the very same page a proper look with shared styling, dark mode and an application shell, without changing any of this underlying logic at all." |

**Shot notes**
- Voice: Chirp 3 HD, ~0.95 rate. Narrate **per sentence** so any line is a one-take re-render, but keep the sentences long — Chirp's metre flows better across a full clause than across short fragments.
- Record the browser at 1920×1080, `deviceScaleFactor: 2`; zoom by cropping, never scaling up.
- Keep the editor at a large font (24–26px) — code legibility is the whole point.
- Target ~3:30.
