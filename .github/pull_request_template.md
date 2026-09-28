## Summary

Briefly describe what this pull request changes and why.

## Related issue

Link the related issue if one exists.

Closes #

## Changes

Describe the important implementation changes.

## Testing

Explain how the change was tested.

Examples:

- PHP syntax check
- Migration parser check
- Installer preflight
- Manual browser testing
- Fresh database installation
- Relevant module testing

## Database changes

- [ ] No database changes
- [ ] Includes a migration
- [ ] Existing installations were considered

## Security and privacy

- [ ] No credentials, tokens, private keys, or production secrets are included
- [ ] No personal or production data is included
- [ ] Authorization and CSRF implications were considered where relevant

## Compatibility

- [ ] PHP 8.1
- [ ] PHP 8.2
- [ ] PHP 8.3
- [ ] PHP 8.4

Check only the versions you actually tested when submitting the pull request.

## Checklist

- [ ] The change is focused and does not include unrelated modifications
- [ ] Documentation was updated where necessary
- [ ] New or changed migrations were checked with `php tools/check-migrations.php`
- [ ] `git diff --check` passes
- [ ] I have read `CONTRIBUTING.md`
