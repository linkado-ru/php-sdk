# Changelog

All notable changes to `linkado-ru/php-sdk` will be documented in this file.

## Unreleased

## 1.1.0 - 2026-10-26

- Add typed authenticated `trackingClicks()->resolve(ResolveTrackingClickData)` with nullable `TrackingClickData`, authoritative order/expiry and bounded timeouts. Public click creation remains browser-owned; no hidden retries or event API changes. Existing event and SSO APIs remain compatible.

## 1.0.0 - 2026-09-19

- Added the initial typed Linkado Events and SSO Links SDK surfaces.
- Added strict event metadata, idempotency, and sensitive-data safeguards.
- Added package, testing, release, and Laravel Boost integration documentation.
