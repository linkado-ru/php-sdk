<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeImmutable;
use Linkado\PhpSdk\Concerns\Data;
use Linkado\PhpSdk\Support\DateTimeNormalizer;

final class TrackingClickData extends Data
{
    public function __construct(
        public readonly string $click_id,
        public readonly string $program_key,
        public readonly string $referral_slug,
        public readonly int $visit_order,
        public readonly DateTimeImmutable $created_at,
        public readonly DateTimeImmutable $expires_at,
    ) {}

    /** @param array<string, mixed> $response */
    public static function fromSaloon(array $response): self
    {
        return new self(
            click_id: $response['click_id'],
            program_key: $response['program_key'],
            referral_slug: $response['referral_slug'],
            visit_order: $response['visit_order'],
            created_at: DateTimeNormalizer::parse($response['created_at']),
            expires_at: DateTimeNormalizer::parse($response['expires_at']),
        );
    }
}
