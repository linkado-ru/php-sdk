<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeInterface;
use Linkado\PhpSdk\Enums\EventType;

final class PaymentRefundedEventData extends EventData
{
    public function __construct(
        string $event_id,
        string $program_key,
        DateTimeInterface|string $occurred_at,
        string $external_customer_id,
        public readonly string $external_payment_id,
        public readonly string $external_refund_id,
        public readonly int $refunded_amount_minor,
        public readonly string $currency,
        ?EventMetadataData $metadata = null,
    ) {
        $this->assertPositiveMinorUnits($refunded_amount_minor, 'refunded_amount_minor');

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
        return EventType::PaymentRefunded;
    }

    protected function eventPayload(): array
    {
        return [
            'external_payment_id' => $this->external_payment_id,
            'external_refund_id' => $this->external_refund_id,
            'refunded_amount_minor' => $this->refunded_amount_minor,
            'currency' => $this->currency,
        ];
    }
}
