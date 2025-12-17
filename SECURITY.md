# Security Policy

## Project Context

SPE (Simple PHP Examples) is an **educational tutorial project** designed to teach modern PHP development patterns. It is intended for local development and learning purposes, not production deployment.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| main    | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability in this project, please report it by:

1. **Opening a GitHub Issue** - For non-sensitive issues that don't expose exploitation details
2. **Email** - For sensitive vulnerabilities, contact mc@netserva.org

### What to Include

- Description of the vulnerability
- Steps to reproduce
- Affected chapter(s) or file(s)
- Potential impact
- Suggested fix (if any)

### Response Timeline

- **Acknowledgment**: Within 48 hours
- **Initial assessment**: Within 7 days
- **Fix or mitigation**: Depends on severity

## Security Considerations

### Educational Code

This codebase demonstrates PHP patterns and may include simplified implementations for teaching purposes. When adapting code for production:

- **Input validation**: Add comprehensive validation beyond examples shown
- **SQL injection**: Use parameterized queries (demonstrated in chapters 07-09)
- **XSS prevention**: Escape all user output appropriately
- **CSRF protection**: Implement tokens for state-changing operations
- **Password hashing**: Use `password_hash()` with `PASSWORD_DEFAULT` (demonstrated in chapters 08-09)
- **Session security**: Configure secure session settings for production

### Known Limitations

| Area | Tutorial Approach | Production Recommendation |
|------|-------------------|---------------------------|
| Error display | Errors shown for learning | Use error logging, hide from users |
| HTTPS | Not enforced | Always use HTTPS |
| CSRF tokens | Not implemented | Required for forms |
| Rate limiting | Not implemented | Add for authentication |
| Input sanitization | Basic examples | Comprehensive validation |

### Database Security

The SQLite databases (`blog.db`, `users.db`) are file-based and stored in the project directory. For production:

- Store databases outside web root
- Use appropriate file permissions
- Consider PostgreSQL/MySQL for multi-user scenarios
- Implement proper backup procedures

## Best Practices Demonstrated

The tutorial does demonstrate several security best practices:

- `declare(strict_types=1)` in all PHP files
- PDO with prepared statements (chapters 07-09)
- Password hashing with `password_hash()` (chapters 08-09)
- Session-based authentication (chapters 06-09)
- Input filtering with `filter_var()` and `htmlspecialchars()`

## Scope

Security reports are accepted for:

- Vulnerabilities in the tutorial code
- Insecure patterns that could mislead learners
- Documentation improvements for security guidance

Out of scope:

- Issues requiring physical access
- Social engineering
- Third-party dependencies (report to upstream)

## Recognition

Contributors who report valid security issues will be acknowledged in the project (unless they prefer to remain anonymous).
