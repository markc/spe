# Tutorial Script Generation Prompt

Use this prompt template to generate narration scripts for SPE video tutorials.

## The Prompt

```
Create a tutorial.txt narration script for XX-Chapter following the format:
duration|URL|text|action

Guidelines:
- Start with a hook (5-6 seconds) that captures attention
- Introduction establishes chapter context (6-7 seconds)
- Live demo sections show the running application
- Code walkthrough sections reference GitHub line numbers
- Each segment should be 4-9 seconds (matches natural speech)
- Use spaces in acronyms for TTS: "P H P", "C S S", "U R L"
- End with conclusion and teaser for next chapter

Read XX-Chapter/public/index.php and identify the key concepts to cover.
Reference specific line numbers in GitHub URLs.
```

## Format Reference

```txt
# Chapter XX: Name - Narration Script
# Format: duration|URL|text|action
# Pronunciation: Use spaces for acronyms (P H P)

# --- Hook ---
5|http://localhost:8080/XX-Chapter/public/index.php|Opening hook line.

# --- Introduction ---
6|http://localhost:8080/XX-Chapter/public/index.php|Context and what we'll learn.

# --- Live Demo ---
5|http://localhost:8080/XX-Chapter/public/index.php|Demo description.
5|http://localhost:8080/XX-Chapter/public/index.php?m=about|Navigation demo.

# --- Code Walkthrough ---
7|https://github.com/markc/spe/blob/main/XX-Chapter/public/index.php#L4-L10|Code explanation.

# --- Conclusion ---
6|http://localhost:8080/XX-Chapter/public/index.php|Summary of what we covered.
4|http://localhost:8080/XX-Chapter/public/index.php|Next chapter teaser.
```

## Duration Guidelines

| Content Type | Duration | Notes |
|--------------|----------|-------|
| Hook | 5-6s | Attention-grabbing statement |
| Introduction | 6-7s | Sets context |
| Demo segments | 4-6s | Show, don't over-explain |
| Code segments | 6-9s | Complex concepts need more time |
| Transitions | 4-5s | Brief connecting statements |
| Conclusion | 5-6s | Summarize key takeaways |

## Action Format (Optional)

For interactive demos, add actions:
- `click:.selector` - Click element matching CSS selector
- `wait:1000` - Wait milliseconds before screenshot
- `eval:code` - Execute JavaScript

Example:
```
5|http://localhost:8080/02-Styled/public/index.php|Toggle dark mode.|click:.theme-toggle
```

## TTS Pronunciation

Acronyms should be spaced for natural speech:
- PHP → "P H P"
- CSS → "C S S"
- HTML → "H T M L"
- URL → "U R L"
- CRUD → "C R U D"
- PDO → "P D O"

## Quality Checklist

- [ ] Hook is engaging and specific
- [ ] Total duration matches target (1-3 minutes for simple chapters, 4-8 for complex)
- [ ] GitHub URLs include line number anchors (#L5-L10)
- [ ] Acronyms are spaced for TTS
- [ ] Transitions flow naturally
- [ ] Conclusion summarizes without repeating
- [ ] Next chapter teaser creates continuity
