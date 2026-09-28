# Contributing to Work Schedule Manager

Thank you for considering contributing to Work Schedule Manager.

The original project is maintained by **QvarcY**.

Repository:

https://github.com/QvarcY/work-schedule-manager

## Before you start

For non-trivial changes, please open an issue first so the proposed
direction can be discussed before significant implementation work is
done.

Small bug fixes, documentation fixes and narrowly scoped improvements
can usually be submitted directly as pull requests.

## Development setup

Requirements:

- PHP 8.1+
- PDO
- pdo_mysql
- mbstring
- MySQL or MariaDB

Copy the example configuration:

```bash
cp .env.example .env
```

Then configure a dedicated local development database.

Run the installer preflight check:

```bash
php tools/install.php --check
```

For a new database:

```bash
php tools/install.php
```

Start the development server:

```bash
php -S 127.0.0.1:8080 -t public tools/dev-router.php
```

## Before submitting a pull request

Run the PHP syntax check for all PHP files.

PowerShell example:

```powershell
Get-ChildItem app,modules,public,tools -File -Recurse -Filter *.php |
    ForEach-Object {
        php -l $_.FullName
        if ($LASTEXITCODE -ne 0) {
            throw "PHP lint failed: $($_.FullName)"
        }
    }
```

Validate SQL migrations:

```bash
php tools/check-migrations.php
```

Run the installer preflight:

```bash
php tools/install.php --check
```

Changes that affect installation or database structure should also be
tested against a fresh empty database.

## Coding guidelines

Keep changes focused and avoid unrelated refactors.

Preserve strict typing in PHP files where it is already used.

Use prepared statements for dynamic database queries.

Escape user-controlled HTML output.

State-changing browser actions must retain CSRF protection.

Do not commit secrets, credentials, database exports or `.env`.

## Modules

Each module should remain self-contained where practical.

A module manifest must remain valid JSON and should preserve the project
license and author metadata.

Database changes belonging to a module should live in that module's
migration directory.

## Licensing

By contributing code to this repository, you agree that your
contribution may be distributed under the repository's
AGPL-3.0-or-later license and applicable additional terms.

Do not remove existing copyright, SPDX, attribution or license notices.

## Attribution

Modified versions must follow the requirements described in:

- `LICENSE`
- `NOTICE.md`
- `ADDITIONAL_TERMS.md`

## Pull requests

A useful pull request should explain:

- what problem it solves;
- what changed;
- how it was tested;
- whether database or configuration changes are required.

Please keep generated files and unrelated formatting changes out of
pull requests wherever possible.

Copyright (C) 2026 QvarcY
SPDX-License-Identifier: AGPL-3.0-or-later
