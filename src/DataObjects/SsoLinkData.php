<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeImmutable;
use Linkado\PhpSdk\Concerns\Data;
use Linkado\PhpSdk\Support\DateTimeNormalizer;

final class SsoLinkData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly DateTimeImmutable $expires_at,
    ) {}

    /** @param array<string, mixed> $response */
    public static function fromSaloon(array $response): self
    {
        return new self(
            id: (string) $response['id'],
            url: (string) $response['url'],
            expires_at: DateTimeNormalizer::parse((string) $response['expires_at']),
        );
    }
}
