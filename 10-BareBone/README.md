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

## Degug Trace Example

Below is how a debug trace looks for the Home plugin when DBG=true in `public/index.php`.

```
[Thu Feb 13 16:28:04 2025] [::1]:34246 Accepted
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Ctx::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Init::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\PluginNav::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\PluginNav::scanPlugins
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\PluginNav::isCacheValid
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\PluginNav::loadCache
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(a, '', '')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(c, '', '')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(g, '', '')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(l, '', '')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(m, 'list', 'list')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(o, 'Home', 'Home')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(p, '1', '1')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(t, 'TopNav', 'TopNav')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Util::ses(x, '', '')
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Plugin::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Plugins\Home\Model::list
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Plugins\Home\View::list
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Themes\TopNav::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::__construct
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::css
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::log
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::nav1
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::head
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::main
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::foot
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::js
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Theme::html
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Init::__toString
[Thu Feb 13 16:28:04 2025] SPE\BareBone\Core\Init::__destruct
[Thu Feb 13 16:28:04 2025] [::1]:34246 [200]: GET /10-BareBone/public?o=Home
[Thu Feb 13 16:28:04 2025] [::1]:34246 Closing
```
