<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeInterface;
use InvalidArgumentException;

abstract class AttributionEventData extends EventData
{
    public function __construct(
        string $event_id,
        string $program_key,
        DateTimeInterface|string $occurred_at,
        string $external_customer_id,
        public readonly ?string $click_id = null,
        public readonly ?string $referral_slug = null,
        ?EventMetadataData $metadata = null,
    ) {
        if ($click_id !== null && $referral_slug !== null) {
            throw new InvalidArgumentException('Only one attribution field may be provided.');
        }

        parent::__construct(
            event_id: $event_id,
            program_key: $program_key,
            occurred_at: $occurred_at,
            external_customer_id: $external_customer_id,
            metadata: $metadata,
        );
    }

    protected function eventPayload(): array
    {
        return [
            ...($this->click_id !== null ? ['click_id' => $this->click_id] : []),
            ...($this->referral_slug !== null ? ['referral_slug' => $this->referral_slug] : []),
        ];
    }
}
