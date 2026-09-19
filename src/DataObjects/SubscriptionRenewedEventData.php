<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeInterface;
use Linkado\PhpSdk\Enums\EventType;

final class SubscriptionRenewedEventData extends EventData
{
    public function __construct(
        string $event_id,
        string $program_key,
        DateTimeInterface|string $occurred_at,
        string $external_customer_id,
        public readonly string $external_payment_id,
        public readonly string $external_subscription_id,
        public readonly int $amount_minor,
        public readonly string $currency,
        ?EventMetadataData $metadata = null,
    ) {
        $this->assertPositiveMinorUnits($amount_minor, 'amount_minor');

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
        return EventType::SubscriptionRenewed;
    }

    protected function eventPayload(): array
    {
        return [
            'external_payment_id' => $this->external_payment_id,
            'external_subscription_id' => $this->external_subscription_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
        ];
    }
}
