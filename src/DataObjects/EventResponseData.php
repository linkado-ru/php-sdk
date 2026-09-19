<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use Linkado\PhpSdk\Concerns\Data;
use Linkado\PhpSdk\Enums\EventStatus;

final class EventResponseData extends Data
{
    /**
     * @param  array<string, mixed>|null  $result
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly string $id,
        public readonly string $event_id,
        public readonly EventStatus $status,
        public readonly ?array $result,
        public readonly array $warnings,
    ) {}

    /** @param array<string, mixed> $response */
    public static function fromSaloon(array $response): self
    {
        return new self(
            id: (string) $response['id'],
            event_id: (string) $response['event_id'],
            status: EventStatus::from((string) $response['status']),
            result: isset($response['result']) && is_array($response['result'])
                ? $response['result']
                : null,
            warnings: isset($response['warnings']) && is_array($response['warnings'])
                ? array_values(array_map('strval', $response['warnings']))
                : [],
        );
    }
}
