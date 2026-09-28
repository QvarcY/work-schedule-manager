# Security Policy

## Supported versions

Security fixes are currently targeted at the latest published version of
Work Schedule Manager.

Older snapshots or modified third-party versions may not receive fixes
from the original project.

## Reporting a vulnerability

Please do not disclose exploitable vulnerabilities through a public
GitHub issue.

Preferred reporting method:

1. Open the repository Security tab.
2. Use GitHub private vulnerability reporting if it is available.
3. Include clear reproduction steps, the affected component and the
   expected security impact.

Repository:

https://github.com/QvarcY/work-schedule-manager

If private vulnerability reporting is not available, contact the project
maintainer through the QvarcY GitHub profile rather than posting
sensitive technical details publicly:

https://github.com/QvarcY

## Sensitive information

Never include the following in bug reports, screenshots or logs:

- `.env` contents;
- database passwords;
- administrator passwords;
- session identifiers;
- private keys;
- production database exports;
- API keys or access tokens.

## Security expectations

Production installations should use HTTPS, `APP_DEBUG=false`, a
dedicated database account and an inaccessible project root with only
`public/` exposed through the web server.

## Scope

Security reports concerning modified forks should normally be directed
to the maintainer of that fork unless the issue also affects the
original Work Schedule Manager codebase.

Copyright (C) 2026 QvarcY
SPDX-License-Identifier: AGPL-3.0-or-later
