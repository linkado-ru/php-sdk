---
name: linkado-php-sdk
description: Use when integrating linkado-ru/php-sdk into Laravel applications for Linkado merchant events, billing events, refunds, transactional outbox delivery, or signed SSO links.
---

# Linkado PHP SDK

## Installation and Configuration

Install `linkado-ru/php-sdk` and create `Linkado\PhpSdk\LinkadoConnector` from environment-backed Laravel config. Never read credentials directly from application code or log connector state.

```bash
composer require linkado-ru/php-sdk
```

```php
use Linkado\PhpSdk\LinkadoConnector;

$linkado = new LinkadoConnector(
    token: config('services.linkado.token'),
    baseUrl: config('services.linkado.base_url'),
);
```

## Event Delivery

- Use `$linkado->events()->send($event)` instead of raw HTTP.
- Create the event and its stable ULID inside the same database transaction as the merchant fact, store the exact payload in a transactional outbox, and reuse it for every attempt.
- Never make synchronous Linkado event calls inside checkout, payment, refund, cancellation, or registration transactions.
- Do not generate a new event ID or mutate payload after a failure.
- Do not include email, phone, address, tax, bank, card, or other PII in event metadata.
- A `200` or `202` means Linkado accepted the event identity. Inspect the typed status: final processing may still be pending or `waiting_dependency`.

Retry connection/timeout failures, `408`, `429` (respect `Retry-After`), and `5xx` according to application policy. Treat other `4xx`, including `409 idempotency_conflict` and validation failures, as permanent until data or code is repaired.

## SSO

Create a typed `CreateSsoLinkData` and call `$linkado->ssoLinks()->create($data)`. Redirect immediately to the returned URL; it contains a five-minute one-time nonce and must never be logged, persisted in analytics, or exposed to untrusted code. Use only the SDK `SsoRedirect` enum, never a user-supplied URL.

