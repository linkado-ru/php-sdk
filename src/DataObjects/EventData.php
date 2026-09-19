<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Linkado\PhpSdk\Concerns\Data;
use Linkado\PhpSdk\Enums\EventType;

abstract class EventData extends Data
{
    public readonly DateTimeInterface|string $occurred_at;

    public function __construct(
        public readonly string $event_id,
        public readonly string $program_key,
        DateTimeInterface|string $occurred_at,
        public readonly string $external_customer_id,
        public readonly ?EventMetadataData $metadata = null,
    ) {
        $this->occurred_at = $occurred_at instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($occurred_at)
            : $occurred_at;
    }

    abstract public function type(): EventType;

    /** @return array<string, mixed> */
    final public function toArray(): array
    {
        return [
            'event_id' => $this->event_id,
            'type' => $this->type()->value,
            'program_key' => $this->program_key,
            'occurred_at' => $this->normalizeValue($this->occurred_at),
            'external_customer_id' => $this->external_customer_id,
            ...$this->eventPayload(),
            ...($this->metadata !== null ? ['metadata' => $this->metadata->toArray()] : []),
        ];
    }

    /** @return array<string, mixed> */
    abstract protected function eventPayload(): array;

    protected function assertPositiveMinorUnits(int $amount, string $field): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("{$field} must be a positive integer.");
        }
    }
}
