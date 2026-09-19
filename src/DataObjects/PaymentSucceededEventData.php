<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use DateTimeInterface;
use InvalidArgumentException;
use Linkado\PhpSdk\Enums\EventType;
use Linkado\PhpSdk\Enums\PaymentKind;

final class PaymentSucceededEventData extends EventData
{
    public function __construct(
        string $event_id,
        string $program_key,
        DateTimeInterface|string $occurred_at,
        string $external_customer_id,
        public readonly string $external_payment_id,
        public readonly int $amount_minor,
        public readonly string $currency,
        public readonly PaymentKind $payment_kind,
        public readonly ?string $external_subscription_id = null,
        ?EventMetadataData $metadata = null,
    ) {
        $requiresSubscription = in_array($payment_kind, [
            PaymentKind::SubscriptionInitial,
            PaymentKind::SubscriptionChange,
        ], true);

        if ($requiresSubscription && $external_subscription_id === null) {
            throw new InvalidArgumentException('external_subscription_id is required for subscription payments.');
        }

        if ( ! $requiresSubscription && $external_subscription_id !== null) {
            throw new InvalidArgumentException('external_subscription_id is prohibited for this payment kind.');
        }

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
        return EventType::PaymentSucceeded;
    }

    protected function eventPayload(): array
    {
        return [
            'external_payment_id' => $this->external_payment_id,
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'payment_kind' => $this->payment_kind->value,
            ...($this->external_subscription_id !== null
                ? ['external_subscription_id' => $this->external_subscription_id]
                : []),
        ];
    }
}
