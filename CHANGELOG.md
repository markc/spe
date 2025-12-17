# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2] - 2024-12-15

### Added
- Chapter 00 introductory video tutorial ([YouTube](https://youtu.be/Pch633WeTHU))
- `00-Tutorial/tutorial.txt` narration script (23 segments, ~4:36 duration)
- `00-Tutorial/scripts/youtube-upload.sh` for automated YouTube uploads with OAuth
- `00-Tutorial/youtube-metadata.json` template for video metadata

### Fixed
- `@types/bun` version specifier in 00-Tutorial package.json ("latest" → "^1.3.4")

## [1.0.1] - 2024-12-15

### Added
- Dependabot configuration for automated dependency updates (Composer, npm, GitHub Actions)
- Dependabot auto-merge workflow for patch/minor updates
- CODEOWNERS file for automatic PR review requests
- Custom 404 page for GitHub Pages
- Full 01-Simple/index.php code example on landing page

### Changed
- Updated `actions/checkout` from v4 to v6
- Disabled strict branch protection (PRs no longer need rebase before merge)

## [1.0.0] - 2024-12-14

### Added

#### Framework
- PHP 8.5 pipe operator (`|>`) demonstrations throughout
- Plugin/Theme architecture with meta.json configuration
- CRUDL operations pattern (Create, Read, Update, Delete, List)
- PSR-4 autoloading via Composer
- SQLite database layer with PDO and QueryType enum
- Session management with flash messages
- Custom CSS (~270 lines) with dark mode support
- No Bootstrap or external CSS frameworks

#### Chapters
- **00-Tutorial**: Video generation pipeline (Playwright + Piper TTS)
- **01-Simple**: Single-file anonymous class with pipe operator
- **02-Styled**: Custom CSS, dark mode, toast notifications
- **03-Plugins**: Plugin architecture introduction
- **04-Themes**: Model/View separation, multiple layouts
- **05-Autoload**: PSR-4 autoloading with Composer
- **06-Session**: PHP session management
- **07-PDO**: SQLite database with QueryType enum
- **08-Users**: User management CRUDL
- **09-Blog**: Full CMS (Auth, Blog, Pages, Categories, Docs)
- **10-YouTube**: YouTube Manager with OAuth and API integration

#### PHP Features Demonstrated
- PHP 8.5: Pipe operator with first-class callables
- PHP 8.4: Asymmetric visibility, `new` without parentheses
- PHP 8.3: Typed constants, `#[\Override]` attribute
- PHP 8.2: Readonly classes
- PHP 8.1: Enums, first-class callables

#### Community
- README with badges and feature highlights
- CONTRIBUTING.md guide
- CODE_OF_CONDUCT.md (Contributor Covenant v2.1)
- SECURITY.md policy
- CHANGELOG.md for release tracking
- LICENSE (MIT)
- GitHub issue templates (bug, feature)
- Pull request template
- GitHub Actions CI workflow
- Branch protection rules
- GitHub Discussions enabled
- GitHub Sponsors (FUNDING.yml)
- GitHub Pages site (https://markc.github.io/spe/)

[Unreleased]: https://github.com/markc/spe/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/markc/spe/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/markc/spe/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/markc/spe/releases/tag/v1.0.0
