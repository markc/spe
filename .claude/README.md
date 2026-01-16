# SPE Claude Resources

This directory contains Claude Code resources for the Simple PHP Engine project: prompts, runbooks, and slash commands that help maintain consistency across documentation and development.

## Directory Structure

```
.claude/
├── README.md              # This file
├── settings.local.json    # Local Claude settings (gitignored)
├── commands/              # Slash command templates
│   └── document-chapter.md
├── runbooks/              # Procedure documentation
│   └── documentation-style-guide.md
└── prompts/               # Reusable prompts
    ├── chapter-readme.md
    └── tutorial-script.md
```

## Quick Reference

### Document a Chapter
```
/document-chapter 03-Plugins
```

### Generate Tutorial Script
Use the prompt in `prompts/tutorial-script.md` to create narration scripts for video tutorials.

## Philosophy

SPE documentation should:
- **Explain why**, not just what
- Use **prose paragraphs** over bullet points for conceptual content
- **Analyze every line** of code being documented
- Show **data flow** through the application
- Connect to **PHP version features** that enable patterns
- **Foreshadow evolution** in later chapters

## Files

### runbooks/documentation-style-guide.md
The master guide for writing SPE documentation. Contains:
- Prompt templates that produce quality output
- Section structure guidelines
- Prose style rules
- Chapter-specific focus areas
- Quality checklist

### prompts/chapter-readme.md
Template prompt for documenting any SPE chapter's README.

### prompts/tutorial-script.md
Template for generating tutorial.txt narration scripts.

### commands/document-chapter.md
Slash command that invokes the documentation workflow.

## Usage Pattern

1. **Read the runbook** to understand the documentation philosophy
2. **Use the prompts** as starting points, customized per chapter
3. **Follow the quality checklist** before finalizing
4. **Update this directory** when better patterns emerge

## What's Gitignored

- `settings.local.json` - Local Claude preferences
- Any files with sensitive data (API keys, credentials)

## Contributing

When you discover prompts or procedures that produce excellent results:
1. Add them to the appropriate subdirectory
2. Document what makes them effective
3. Include before/after examples where helpful
