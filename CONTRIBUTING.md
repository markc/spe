# Contributing to SPE

Thanks for your interest in contributing to Simple PHP Examples!

## Getting Started

### Requirements

- PHP 8.5+ (for pipe operator `|>`)
- Composer
- Git

### Setup

```bash
git clone https://github.com/markc/spe
cd spe
composer install
php -S localhost:8000
```

Open http://localhost:8000 to browse chapters.

## Code Style

### PHP Version Features

Use modern PHP features consistently:

```php
<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)

// PHP 8.5: Pipe operator for transformations
$value = $input |> trim(...) |> strtolower(...);

// PHP 8.4: Asymmetric visibility
public private(set) string $name;

// PHP 8.3: Typed constants
private const string DEFAULT = 'home';

// PHP 8.3: Override attribute
#[\Override] public function list(): array { }

// PHP 8.2: Readonly classes
final readonly class Config { }

// PHP 8.1: Enums
enum Status { case Active; case Inactive; }
```

### File Headers

Every PHP file must include:

```php
<?php declare(strict_types=1);
// Copyright (C) 2015-2025 Mark Constable <mc@netserva.org> (MIT License)
```

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `HomeModel`, `TopNav` |
| Methods | camelCase | `getUser()`, `buildNav()` |
| Constants | UPPER_SNAKE | `DEFAULT_THEME` |
| Variables | camelCase | `$userName`, `$navItems` |
| Files | Match class | `HomeModel.php` |

### No External Dependencies

- No Bootstrap or external CSS frameworks
- No JavaScript frameworks (vanilla JS only)
- Minimal Composer dependencies (autoloading only for 05-10)
- Unicode emoji for icons, not icon libraries

## Project Structure

### Chapters

Each chapter builds on the previous:

| Chapter | Adds |
|---------|------|
| 01-Simple | Single-file, pipe operator |
| 02-Styled | Custom CSS, dark mode |
| 03-Plugins | Plugin architecture |
| 04-Themes | Model/View separation |
| 05-Autoload | PSR-4, Composer |
| 06-Session | Session management |
| 07-PDO | SQLite database |
| 08-Users | User CRUDL |
| 09-Blog | Full CMS |
| 10-YouTube | OAuth, API |

### Directory Layout (05-10)

```
XX-Chapter/
├── public/index.php
└── src/
    ├── Core/           # Framework classes
    ├── Plugins/        # Feature plugins
    │   └── Name/
    │       ├── NameModel.php
    │       ├── NameView.php
    │       └── meta.json
    └── Themes/         # Layout themes
```

## Making Changes

### 1. Create a Branch

```bash
git checkout -b feature/your-feature
# or
git checkout -b fix/bug-description
```

### 2. Make Changes

- Follow code style above
- Test with `php -S localhost:8000`
- Check both light and dark modes for UI changes
- Verify affected chapters still work

### 3. Commit

Write clear commit messages:

```
Add user avatar support to 08-Users

- Add avatar upload field to user form
- Store avatars in uploads/ directory
- Display avatar in user list and profile
```

### 4. Submit PR

- Push your branch: `git push -u origin feature/your-feature`
- Open a PR on GitHub
- Fill out the PR template
- Link related issues with `Closes #123`

## Types of Contributions

### Bug Fixes

1. Check existing issues first
2. Create an issue if none exists
3. Reference the issue in your PR

### New Features

1. Open an issue to discuss the feature first
2. Wait for feedback before implementing
3. Keep changes minimal and focused

### Documentation

- Fix typos or unclear explanations
- Add examples to README files
- Improve code comments

### New Chapters

New chapters should:
- Build on the previous chapter
- Introduce one main concept
- Include a README.md explaining the additions
- Follow existing patterns

## Testing

### Manual Testing

```bash
# Test specific chapter
cd 09-Blog/public && php -S localhost:8080

# Test from root (all chapters)
php -S localhost:8000
```

### Checklist

- [ ] PHP syntax valid (`php -l file.php`)
- [ ] No PHP warnings/errors in browser console
- [ ] Works in both light and dark mode
- [ ] Responsive on mobile viewport
- [ ] Previous chapters unaffected

## Questions?

- Check the [YouTube tutorials](https://www.youtube.com/playlist?list=PLM0Did14jsitwKl7RYaVrUWnG1GkRBO4B)
- Open a [Question issue](https://github.com/markc/spe/issues/new?template=question.md)
- Read the chapter README files

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
