<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeInterface;
use Linkado\PhpSdk\Enums\EventType;

final class SubscriptionCancelledEventData extends EventData
{
    public function __construct(
        string $event_id,
        string $program_key,
        DateTimeInterface|string $occurred_at,
        string $external_customer_id,
        public readonly string $external_subscription_id,
        ?EventMetadataData $metadata = null,
    ) {
        parent::__construct(
            event_id: $event_id,
            program_key: $program_key,
            occurred_at: $occurred_at,
            external_customer_id: $external_customer_id,
            metadata: $metadata,
        );
    }

    public function type(): EventType
    {
        return EventType::SubscriptionCancelled;
    }

    protected function eventPayload(): array
    {
        return ['external_subscription_id' => $this->external_subscription_id];
    }
}
