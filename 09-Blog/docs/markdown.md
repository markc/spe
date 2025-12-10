# Markdown Parser

SPE includes a minimal (~70 lines) GitHub-flavored Markdown parser in `Util::md()`. Based on Slimdown by Johnny Broadway (MIT License).

## Supported Syntax

### Headings

```markdown
# Heading 1
## Heading 2
### Heading 3
#### Heading 4
##### Heading 5
###### Heading 6
```

### Text Formatting

```markdown
**bold** or __bold__
*italic* or _italic_
~~strikethrough~~
```

### Links and Images

```markdown
[Link text](https://example.com)
![Alt text](image.png)
```

### Code

```markdown
Inline `code` here

​```php
// Fenced code block with language
$x = 1;
​```
```

### Blockquotes

```markdown
> This is a blockquote
> Multiple lines work
```

### Lists

```markdown
- Unordered item 1
- Unordered item 2
* Also works with asterisks
+ And plus signs

1. Ordered item 1
2. Ordered item 2
```

### Horizontal Rules

```markdown
---
***
___
```

### Tables (GFM)

```markdown
| Header 1 | Header 2 | Header 3 |
|----------|:--------:|---------:|
| Left     | Center   | Right    |
| Cell     | Cell     | Cell     |
```

Alignment markers:
- `:---` Left aligned (default)
- `:---:` Center aligned
- `---:` Right aligned

## Usage

```php
use SPE\Blog\Core\Util;

$html = Util::md($markdownText);
```

## Implementation Details

### Processing Order

1. **Protect fenced code blocks** - Extract and store for later
2. **Protect inline code** - Extract and store for later
3. **Tables** - Must be before other block elements
4. **Block elements** - Headings, hr, blockquotes
5. **Lists** - ul/ol with consecutive item merging
6. **Inline elements** - Images, links, bold, italic, strike
7. **Escape HTML** - Security: htmlspecialchars()
8. **Restore code blocks** - Put protected content back
9. **Wrap paragraphs** - Split on blank lines, wrap in `<p>`

### Marker System

The parser uses control characters as temporary markers to prevent HTML escaping of legitimate tags:

```php
$L = "\x02";  // Left angle bracket marker
$R = "\x03";  // Right angle bracket marker

// During processing
$s = "{$L}strong{$R}text{$L}/strong{$R}";

// After HTML escaping, convert markers to actual brackets
$s = str_replace([$L, $R], ['<', '>'], $s);
```

### Code Block Protection

Code blocks are extracted and replaced with numbered placeholders:

```php
// Store code block
$b[] = "<pre><code>...</code></pre>";
return "\x00" . (count($b) - 1) . "\x00";

// Later, restore
$s = preg_replace_callback('/\x00(\d+)\x00/', fn($m) => $b[(int)$m[1]], $s);
```

### Security

All user content is escaped via `htmlspecialchars()`:

```php
// Escape everything except our marker tags
$s = htmlspecialchars($s, ENT_NOQUOTES);
```

### Paragraph Wrapping

Non-block content is wrapped in `<p>` tags:

```php
// Split on blank lines
$blocks = preg_split('/\n\n/', $s);

// Wrap non-block content
array_map(function($block) {
    // Skip if starts with block element
    if (preg_match('/^<(?:h[1-6]|ul|ol|blockquote|hr|pre|table)/', $block))
        return $block;
    // Wrap in paragraph, convert single newlines to <br>
    return '<p>' . preg_replace('/\n/', '<br>', $block) . '</p>';
}, $blocks);
```

## Output Examples

### Input

```markdown
# Welcome

This is **bold** and *italic*.

- Item 1
- Item 2

| Name | Age |
|------|-----|
| John | 30  |
```

### Output

```html
<h1>Welcome</h1>
<p>This is <strong>bold</strong> and <em>italic</em>.</p>
<ul><li>Item 1</li><li>Item 2</li></ul>
<table><thead><tr><th style="text-align:left">Name</th><th style="text-align:left">Age</th></tr></thead><tbody><tr><td style="text-align:left">John</td><td style="text-align:left">30</td></tr></tbody></table>
```

## Limitations

- No nested lists (flat only)
- No task lists `- [ ]`
- No footnotes
- No definition lists
- No autolinks (URLs must use `[text](url)` syntax)
- Tables require pipe delimiters on both ends

## Extending

To add new syntax, insert a regex replacement in the appropriate processing section:

```php
// Example: Add ==highlight== syntax
// Add after other inline elements (step 5)
$s = preg_replace('/==(.+?)==/', "{$L}mark{$R}\$1{$L}/mark{$R}", $s);
```
