# Chapter README Documentation Prompt

Use this prompt template to generate comprehensive documentation for any SPE chapter.

## The Prompt

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

## Key Elements

### "Ultrathink"
Signals deep analysis is required. Claude will spend more time reasoning about architectural decisions rather than surface-level descriptions.

### "Analyze every line, property and method"
Forces granular examination of the code. Every constant, property, method parameter, and return value should be explained.

### "Describe why it is structured this way"
Shifts focus from "what" to "why". The documentation should justify design decisions, not just describe them.

### "Longer paragraphs rather than bullet points"
Sets the prose style expectation. Conceptual sections should read like technical essays.

### "The key point of this chapter"
Identifies the focal file. For most chapters this is `public/index.php`, but later chapters may focus on specific classes.

## Customization Per Chapter

Replace `XX-Chapter` with the actual chapter name and adjust the focus:

| Chapter | Primary Focus |
|---------|---------------|
| 01-Simple | `public/index.php` - anonymous class, pipe operator |
| 02-Styled | `public/index.php` + `spe.css` + `spe.js` |
| 03-Plugins | `public/index.php` - plugin architecture |
| 04-Themes | `public/index.php` - theme inheritance |
| 05-Autoload | `composer.json` + `src/` structure |
| 06-Session | Session handling in `src/Core/` |
| 07-PDO | `src/Core/Db.php` - database abstraction |
| 08-Users | `src/Plugins/Users/` - auth flow |
| 09-Blog | Full CMS patterns across all files |

## Expected Output

The resulting README should:
- Open with a paragraph establishing chapter significance
- Use concept-focused section headings
- Explain code mechanics in flowing prose
- Quote actual code syntax inline
- Connect patterns to PHP version features
- Justify design decisions
- Foreshadow evolution in later chapters
- Close with running instructions

See `docs/01-Simple/README.md` for the exemplar output.
