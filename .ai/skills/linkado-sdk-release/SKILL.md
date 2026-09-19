---
name: linkado-sdk-release
description: Use when preparing Linkado PHP SDK versions, SemVer decisions, changelog entries, release notes, Composer validation, Git tags, Packagist publication, or post-release checks.
---

# Linkado SDK Release

Read `RELEASING.md`, the current diff, and Git status before release work.

## Release Rules

- Package name is `linkado-ru/php-sdk`.
- Keep `composer.json` versionless; Packagist versions come from Git tags.
- Use SemVer: patch for compatible fixes/docs, minor for additive public API, major for breaking constructors, methods, namespaces, requirements, or behavior.
- Update `CHANGELOG.md` and public documentation before a release.
- Never stage, commit, push, create or move tags, publish, or update Packagist without an explicit user request for that external action.
- Never rewrite a published tag; issue a new patch release.

## Release Gate

Run:

```bash
composer validate --strict
composer test
composer pint
composer test
git diff --check
git status --short
```

Only after the intended commit is on the release branch may an authorized release create an annotated `vX.Y.Z` tag and push it for Packagist discovery.

