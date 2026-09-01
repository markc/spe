# Full DCS shell

SPE now wears the complete DCS (Dual Carousel Sidebars) interface from <https://dcs.spa> —
the root index, chapters 02–09 and the generated documentation site.

## What changed

- `docs/base.css`, `docs/site.css`, `docs/base.js` are vendored **byte-identical** from
  dcs.spa (root `base.css` / `site.css` / `base.js` are symlinks to them). The pinned commit
  lives in `dcs-upstream.json`; `_bin/dcs-sync` (Mix) verifies the files against GitHub at
  that commit and fails on any local edit, `_bin/dcs-sync --update <sha>` re-vendors. Never
  patch these files here — change dcs.spa, then update.
- SPE's own CSS is the small `docs/spe.css` (root `spe.css` symlink): the `--php` brand
  token, a plain scheme-surface page background (dcs.spa paints a photo), a purple gradient
  for the Blog hero, `.sidebar-user`. Load order: `base.css`, `site.css`, `spe.css`.
- `md.js` stays SPE's own (it renders the docs pages) and is not synced.
- Every shell has **two panels per side** — enough to show the carousel without turning a
  PHP tutorial into a UI demo: left **Navigation · About** (root: Navigation · Chapters;
  docs: Chapters · About), right **Appearance · Settings**. Appearance is dcs.spa's panel
  (light/dark, slide/fade, narrow/normal/wide, per-side width spinners, six schemes);
  Settings keeps the `.theme-toggle` link (and, in 08–09, the login/logout block).
- `$schemes` data became `[dot-colour, label, key]` triples for the six dcs.spa schemes
  (Ocean = `default`, Crimson, Stone, Forest, Sunset, Mono). The PHP that renders them —
  `array_map` + `sprintf` through the pipe operator — is unchanged in shape.
- 09-Blog's Home page (`?o=Home`) is the one place the DCS **marketing components** are
  used (hero, section header, service cards, CTA buttons) — the static-page example.
  `.reveal` is deliberately not used: it needs dcs.spa's `site.js`, which SPE does not load.
- `00-Tutorial/scripts/drive-firefox.mjs` forces the pinned layout with the per-side
  `--sw-l` / `--sw-r` widths; the 1024 CSS-px capture gets 200 / 624 / 200 by dcs.spa's
  defaults (which were themselves taken from SPE's earlier fork).

## What did not change

The PHP being taught. Chapters remain self-contained snapshots; only `Theme` markup and the
scheme data moved. `composer check` (manifest, Mago, 84 Pest tests) passes.
