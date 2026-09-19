# Linkado PHP SDK Agent Instructions

This repository is the official PHP SDK for Linkado. It is a Composer library for PHP 8.2+ built with Saloon v4, Pest, and Pint.

## Local Skills

- Use `linkado-sdk-development` when changing endpoints, DTOs, Saloon requests/resources, serialization, response hydration, tests, or public documentation.
- Use `linkado-sdk-release` for SemVer, changelog, release notes, tags, Packagist, or work based on `RELEASING.md`.

The `.ai` skills are canonical. `.agents/skills` contains aliases for cross-runtime discovery. Keep `resources/boost/skills/linkado-php-sdk` package-facing for Laravel applications that install this package.

## SDK Rules

- Treat the Linkado runtime routes, FormRequests, API Resources, enums, and feature tests as the source of truth.
- Preserve public compatibility within a major version. Never reorder existing public DTO constructor parameters; append additive optional fields with safe defaults.
- Model endpoint changes as `DataObject + Request + Resource method + typed response DTO + Pest tests + README`.
- Require callers to provide stable event IDs. Never generate or replace an event ID during delivery or retry.
- Do not add hidden retries, transactional outbox behavior, background jobs, Laravel bindings, or business logic to the SDK.
- Never log Bearer credentials, SSO URLs/nonces, or prohibited PII.
- Update `README.md` and `CHANGELOG.md` for public behavior changes.

## Verification

- Run `composer validate --strict` after package metadata changes.
- Run `composer test` for SDK changes.
- Run `composer pint` after PHP edits, then run `composer test` again.
- Run `git diff --check` before finishing.

