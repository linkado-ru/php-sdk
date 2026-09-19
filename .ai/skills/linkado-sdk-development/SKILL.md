---
name: linkado-sdk-development
description: Use when adding or changing Linkado PHP SDK endpoints, Saloon connectors, requests, resources, DTOs, enums, event payloads, response hydration, Pest tests, README examples, or public compatibility.
---

# Linkado SDK Development

Use the current Linkado API implementation and its feature tests as the contract. Do not infer endpoints or fields from product plans when runtime code is available.

## Implementation Contract

- Follow `DataObject + Request + Resource method + typed response DTO + Pest tests + README`.
- Keep `LinkadoConnector` as the only main entry point.
- Group public scenarios under resources; requests own HTTP method, endpoint, serialization, and hydration.
- Model finite wire values as backed enums and response timestamps as `DateTimeImmutable`.
- Request dates may accept `DateTimeInterface|string` and must serialize date objects as RFC3339.
- Omit nullable optional wire fields without dropping `false`, `0`, or valid empty strings.
- Preserve raw-array construction through `Data::from()` where supported.

## Compatibility and Delivery

- Public DTO constructors are SemVer contracts. Never reorder or insert parameters before existing parameters within a major version.
- Require a caller-provided stable `event_id`; never generate a replacement after an error or during retry.
- The merchant application owns its transactional outbox, retry schedule, and delivery policy. The SDK must not hide them.
- Preserve Linkado's `202` first-delivery, `200` duplicate, and `409` conflict semantics.
- Do not add automatic retries. Consumers must be able to inspect Saloon responses and `Retry-After`.

## Security

- Event metadata is limited to the Linkado allowlist, scalar values, and 4096 canonical JSON bytes.
- Reject common PII-shaped metadata. Never log Bearer credentials, SSO URLs/nonces, or PII.
- Use typed SSO redirect values; never accept arbitrary redirect URLs.

## Checks

- Use Saloon `MockClient` and `MockResponse`; call `Config::preventStrayRequests()`.
- Assert exact method, URL, headers, payload, response hydration, and error behavior.
- Run `composer test`, `composer pint`, `composer test`, and `git diff --check`.

