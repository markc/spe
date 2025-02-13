# SPE::10 BareBone

_Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)_

## Overview

This is a modular PHP framework that demonstrates a plugin-based architecture with multiple themes. It includes a Plugin system with CRUDL (Create, Read, Update, Delete, List) operations and a Theme system with multiple layouts (only a Bootstrap5 TopNav for now). This BareBone sub-project only has the Core classes plus two simply plugins; Home and Example. The purpose of this sub-project is to be able to start a fresh project from a simple baseline and add whatever plugins you care to following the guide below and the two simple demo Plugins.

Let me break down the exact flow for both demo plugins:

## Example Plugin Flow

1. Initial Setup:
   - `$pm = 'SPE\BareBone\Plugins\Example\Model'`
   - `$t1 = 'SPE\BareBone\Plugins\Example\View'`
   - `$t2 = 'SPE\BareBone\Themes\TopNav'`

2. Plugin Action Phase:
   - `Example\Model::list()` is called
   - Sets data in `$ctx->ary`
   - `Example\View::list()` exists, so it's called
   - Renders `$ctx->ary` into `$ctx->out['main']`

3. Theme Initialization:
   - `$theme1 = new Example\View($ctx)` // Plugin view
   - `$theme2 = new TopNav($ctx)` // Base theme

4. Output Section Population:
   - For each section in `$ctx->out` (doc, css, log, nav1, nav2, head, main, foot, js):
   - `Example\View` has all these methods
   - So `Example\View`'s methods are used for every section
   - Theme methods are never needed as fallback

5. Final HTML Rendering:
   - `Example\View::html()` exists
   - So `Example\View::html()` is used to render final `$ctx->buf`
   - Produces custom HTML structure from `Example\View`

## Home Plugin Flow

1. Initial Setup:
   - `$pm = 'SPE\BareBone\Plugins\Home\Model'`
   - `$t1 = 'SPE\BareBone\Plugins\Home\View'`
   - `$t2 = 'SPE\BareBone\Themes\TopNav'`

2. Plugin Action Phase:
   - `Home\Model::list()` is called
   - Sets data in `$ctx->ary`
   - `Home\View::list()` exists, so it's called
   - Renders `$ctx->ary` into `$ctx->out['main']`

3. Theme Initialization:
   - `$theme1 = new Home\View($ctx)` // Plugin view
   - `$theme2 = new TopNav($ctx)` // Base theme

4. Output Section Population:
   - For each section in `$ctx->out` (doc, css, log, nav1, nav2, head, main, foot, js):
   - `Home\View` only has `list()` method
   - So for each partial method:
     - Checks `Home\View` first - not found
     - Falls back to Theme methods
   - Result: All partials come from `Theme.php`

5. Final HTML Rendering:
   - `Home\View::html()` doesn't exist
   - So falls back to `Theme::html()`
   - Produces standard HTML structure from Theme

### The beauty of this system is:

1. Plugins can be minimal (like Home) with just action methods (list, create, etc.)
   - They automatically get all the layout/partial functionality from Theme

2. Or plugins can be fully custom (like Example) with their own partials
   - They can override any/all parts of the rendering process

3. Different themes can be swapped in/out
   - Providing different layouts while keeping the same plugin logic

### This flexibility allows:

- Simple plugins that focus just on data/content
- Complex plugins that need custom rendering
- Theme system for consistent layouts
- Mix-and-match of plugin views and theme partials
