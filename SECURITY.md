# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅ Yes    |

## Reporting a Vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Instead, report vulnerabilities privately via **GitHub Security Advisories**:

1. Go to the repository's **Security** tab
2. Click **"Report a vulnerability"**
3. Fill in the details

We will acknowledge your report within 48 hours and aim to release a fix within 14 days for critical issues.

## Security Principles

- All state-changing endpoints require `ROLE_PIMCORE_USER` or `ROLE_PIMCORE_ADMIN`
- No secrets or credentials are stored in source code
- Input is validated via Symfony Validator constraints before persistence
- No raw SQL — all queries use Doctrine ORM/DBAL
- No `eval()`, `exec()`, or shell execution
