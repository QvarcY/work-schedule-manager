# Changelog

All notable changes to Work Schedule Manager will be documented in this
file.

The format is based loosely on Keep a Changelog.

## [Unreleased]

### Added

- Core translation runtime with fallback language support
- Latvian and English catalogues for the bundled application interface and
  module workflows
- Per-user language preference and a language selector for anonymous sessions
- Module-owned translation catalogues and localized module metadata
- Translation management module with coverage reporting, JSON export and
  installation-level override import
- Translation catalogue validation in the CLI tooling and CI

## [1.0.0] - 2026-09-29

### Added

- Fresh-install CLI installer
- Environment preflight validation
- Database migration runner
- Migration parser verification tool
- Local development router
- Modular employee and scheduling features
- Role and permission system
- Employee profiles
- Internal notifications
- Optional email notifications
- Day-off requests
- Schedule acknowledgements
- Schedule change history
- User invitations
- System status module
- GitHub Actions CI for PHP 8.1, 8.2, 8.3, and 8.4
- Bug report and feature request issue forms
- Pull request template
- Repository screenshots and public project documentation
- AGPL-3.0-or-later licensing
- QvarcY attribution and project notices
- GitHub funding configuration

### Security

- Secure session cookie defaults
- Strict session mode
- HTTP-only cookies
- SameSite cookie protection
- Invitation tokens stored as hashes
- Public self-registration removed
- Environment secrets excluded from version control
- Fresh-install protection against accidental installation over an
  existing populated database

Initial public release.

---

Copyright (C) 2026 QvarcY
SPDX-License-Identifier: AGPL-3.0-or-later
