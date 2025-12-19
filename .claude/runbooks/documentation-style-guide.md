# SPE Documentation Runbook

_A guide for creating comprehensive, prose-style documentation for SPE chapters_

This runbook captures the methodology for writing extensive, analytical documentation that explains not just what code does, but why it exists and how it works at every level.

## Philosophy

The goal is to create documentation that reads like a technical essay, not a feature list. Readers should come away understanding the architectural decisions, the data flow, and the reasoning behind each line of code. Bullet points and quick-reference sections have their place, but the core documentation should be narrative prose that teaches through explanation.

## The Prompt Template

When asking Claude to document an SPE chapter, use this structure:

```
Look at docs/XX-Chapter/README.md and completely refactor it to extensively
describe how XX-Chapter actually works rather than using some headline features.
Use longer paragraphs rather than highlight headings and bullet points.

You can use XX-Chapter/tutorial.txt as a guide but look closely at
XX-Chapter/public/index.php itself (the key point of this chapter) and analyze
every line, property and method and describe each step and especially why this
lightweight modern PHP code flow is used for this example.

Describe why it is structured this way. Ultrathink.
```

### Key Phrases That Produce Better Output

- **"Ultrathink"** - Signals deep analysis is required, not surface-level description
- **"Analyze every line, property and method"** - Forces granular examination
- **"Describe why it is structured this way"** - Focuses on architectural reasoning
- **"Longer paragraphs rather than bullet points"** - Sets prose style expectation
- **"The key point of this chapter"** - Identifies the focal file/concept
- **"Lightweight modern PHP code flow"** - Emphasizes the design philosophy

## Pre-Documentation Checklist

Before writing documentation for a chapter, gather these files:

1. **The main source file** - Usually `XX-Chapter/public/index.php` or `XX-Chapter/src/*.php`
2. **The tutorial.txt** - Contains the narrative flow and key concepts
3. **The existing README** - Understand what's already documented
4. **Related files** - CSS, JS, or config files the chapter introduces

## Documentation Structure

### Opening Paragraph
Start with a single paragraph that establishes what this chapter accomplishes and why it matters in the progression of the series. Set expectations for what the reader will learn.

### Section Headings
Use descriptive headings that indicate concepts, not just code elements:
- Good: "The Core Pattern: Echo a Self-Rendering Object"
- Bad: "The Anonymous Class"

- Good: "Request Processing: The Pipe Operator"
- Bad: "The Constructor"

### Section Content Guidelines

Each section should:

1. **Explain the code's purpose** - What problem does this solve?
2. **Walk through the mechanics** - How does it actually work?
3. **Connect to PHP features** - Which language features enable this?
4. **Justify the design** - Why this approach over alternatives?
5. **Foreshadow evolution** - How does this pattern grow in later chapters?

### Code References
When referencing code, quote the actual syntax inline:
```
The expression `($_REQUEST['m'] ?? '') |> trim(...) |> htmlspecialchars(...)`
reads from left to right as a data transformation pipeline.
```

Don't just say "the pipe operator is used" - show the actual code being discussed.

### Comparisons
Where helpful, show what the alternative would look like:
```
Compare this to the nested equivalent:
`(($p = htmlspecialchars(trim($_REQUEST['m'] ?? ''))) ? $p : self::DEFAULT)`.
The pipe version expresses the same logic but reads in the order the
operations actually occur.
```

## What to Analyze Per Chapter

### For Each PHP File
- Opening declarations (`declare`, `namespace`, `use`)
- Class structure (anonymous, named, readonly, final)
- Constants (typed, visibility, purpose)
- Properties (types, visibility, asymmetric access)
- Constructor (initialization logic, dependency handling)
- Methods (public interface, internal helpers)
- Magic methods (`__toString`, `__construct`, `__invoke`)
- Return values and output generation

### For Each New Concept
- What PHP version introduced it
- What problem it solves
- How it's used in this specific code
- What the alternative would be without it

### For Each Architectural Pattern
- Why this pattern exists
- How it will evolve in later chapters
- What tradeoffs it represents
- How it connects to broader software design principles

## Prose Style Guidelines

### Paragraph Length
Aim for 3-6 sentences per paragraph. Each paragraph should develop a single idea completely before moving to the next.

### Technical Precision
Use exact terminology. Don't say "the class saves the page name" when you mean "the constructor assigns the validated route identifier to the `$page` property."

### Active Voice
Prefer "The constructor processes the request" over "The request is processed by the constructor."

### Connecting Ideas
Use transitional phrases to show relationships:
- "This matters because..."
- "The reason for this separation..."
- "What makes this pattern powerful is..."
- "Consider why these specific properties exist..."

### Avoid
- Bullet point lists for conceptual explanations
- "Simply" or "just" (dismissive of complexity)
- "Obviously" or "clearly" (assumes reader knowledge)
- Unexplained jargon

## Chapter-Specific Focus Areas

### 01-Simple
- The `echo new class {}` single-statement pattern
- Pipe operator chains for data transformation
- Anonymous class lifecycle (construct → toString → output)

### 02-Styled
- External asset loading (CSS, JS)
- CSS custom properties for theming
- JavaScript integration patterns

### 03-Plugins
- Plugin architecture emergence
- CRUDL method pattern
- Separation of routing from content

### 04-Themes
- Model/View separation
- Theme inheritance and overrides
- Multiple layout strategies

### 05-Autoload
- PSR-4 autoloading
- Namespace organization
- Composer integration

### 06-Session
- Session management
- Flash messages
- State persistence

### 07-PDO
- Database abstraction
- QueryType enum pattern
- Prepared statements

### 08-Users
- Authentication flow
- Password hashing
- User management CRUDL

### 09-Blog
- Full CMS patterns
- Content types (posts, pages, docs)
- Category relationships

## Quality Checklist

Before finalizing documentation, verify:

- [ ] Every public method is explained
- [ ] Every property's purpose is clear
- [ ] PHP version features are identified
- [ ] Data flow through the application is traced
- [ ] Design decisions are justified, not just described
- [ ] Code examples use actual syntax from the file
- [ ] Transitions connect sections logically
- [ ] Opening paragraph sets proper expectations
- [ ] Closing section points to next chapter's evolution
- [ ] No orphaned bullet points in conceptual sections
- [ ] Running instructions are included and tested

## Example Transformation

### Before (Bullet-Style)
```markdown
## PHP 8.5 Features
- Pipe operator `|>` for data transformation
- Used in constructor for input processing
- Also used in navigation generation
```

### After (Prose-Style)
```markdown
## Request Processing: The Pipe Operator

The constructor contains the application's entire request-handling logic,
and it demonstrates why the PHP 8.5 pipe operator represents such a
significant language improvement. The expression
`($_REQUEST['m'] ?? '') |> trim(...) |> htmlspecialchars(...) |> (fn($p) => $p ?: self::DEFAULT)`
reads from left to right as a data transformation pipeline.

The process begins with `$_REQUEST['m'] ?? ''`, which retrieves the `m`
parameter from the query string or returns an empty string if it's missing.
This value then flows through the pipe operator to `trim(...)`, a first-class
callable that removes whitespace from both ends of the string...
```

## Maintenance

When chapter code changes:
1. Re-read the source files
2. Identify what changed and why
3. Update affected documentation sections
4. Verify running instructions still work
5. Check that future-chapter references remain accurate

This runbook itself should evolve as better documentation patterns emerge.
