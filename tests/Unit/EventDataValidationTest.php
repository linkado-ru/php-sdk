<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\CustomerCreatedEventData;
use Linkado\PhpSdk\DataObjects\PaymentRefundedEventData;
use Linkado\PhpSdk\DataObjects\PaymentSucceededEventData;
use Linkado\PhpSdk\Enums\EventStatus;
use Linkado\PhpSdk\Enums\EventType;
use Linkado\PhpSdk\Enums\PaymentKind;

it('hydrates nested metadata and payment enums from raw arrays', function (): void {
    $event = PaymentSucceededEventData::from([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00Z',
        'external_customer_id' => 'customer-123',
        'external_payment_id' => 'invoice-100',
        'amount_minor' => 159900,
        'currency' => 'RUB',
        'payment_kind' => 'subscription_change',
        'external_subscription_id' => 'subscription-10',
        'metadata' => ['plan_code' => 'pro'],
    ]);

    expect($event->payment_kind)->toBe(PaymentKind::SubscriptionChange)
        ->and($event->metadata?->plan_code)->toBe('pro')
        ->and($event->toArray()['metadata'])->toBe(['plan_code' => 'pro']);
});

it('omits nullable event fields without dropping valid falsey metadata', function (): void {
    $event = CustomerCreatedEventData::from([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00Z',
        'external_customer_id' => 'customer-123',
        'metadata' => [
            'source' => '',
            'plan_code' => 0,
            'billing_reason' => false,
        ],
    ]);

    expect($event->toArray())->toBe([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'type' => 'customer_created',
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00Z',
        'external_customer_id' => 'customer-123',
        'metadata' => [
            'source' => '',
            'plan_code' => 0,
            'billing_reason' => false,
        ],
    ]);
});

it('snapshots mutable event timestamps to keep retry payloads stable', function (): void {
    $occurredAt = new DateTime('2026-08-06T10:00:00Z');
    $event = CustomerCreatedEventData::from([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => $occurredAt,
        'external_customer_id' => 'customer-123',
    ]);
    $firstPayload = $event->toArray();

    $occurredAt->modify('+1 hour');

    expect($event->toArray())->toBe($firstPayload)
        ->and($event->toArray()['occurred_at'])->toBe('2026-08-06T10:00:00+00:00');
});

it('rejects simultaneous click and referral attribution', function (): void {
    expect(fn () => CustomerCreatedEventData::from([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00Z',
        'external_customer_id' => 'customer-123',
        'click_id' => 'click-1',
        'referral_slug' => 'partner-one',
    ]))->toThrow(InvalidArgumentException::class, 'Only one attribution field');
});

it('enforces subscription identity for every payment kind', function (
    string $kind,
    ?string $subscriptionId,
    bool $valid,
): void {
    $create = fn () => new PaymentSucceededEventData(
        event_id: '01K5G6XYBN7QPF9G0AJM1T2E3R',
        program_key: 'program-public-key',
        occurred_at: '2026-08-06T10:00:00Z',
        external_customer_id: 'customer-123',
        external_payment_id: 'invoice-100',
        amount_minor: 159900,
        currency: 'RUB',
        payment_kind: PaymentKind::from($kind),
        external_subscription_id: $subscriptionId,
    );

    if ($valid) {
        expect($create())->toBeInstanceOf(PaymentSucceededEventData::class);

        return;
    }

    expect($create)->toThrow(InvalidArgumentException::class, 'external_subscription_id');
})->with([
    'initial with subscription' => ['subscription_initial', 'subscription-10', true],
    'initial without subscription' => ['subscription_initial', null, false],
    'change with subscription' => ['subscription_change', 'subscription-10', true],
    'change without subscription' => ['subscription_change', null, false],
    'top up without subscription' => ['top_up', null, true],
    'top up with subscription' => ['top_up', 'subscription-10', false],
    'one time without subscription' => ['one_time', null, true],
    'one time with subscription' => ['one_time', 'subscription-10', false],
]);

it('rejects nonpositive minor unit amounts', function (int $amount): void {
    expect(fn () => PaymentRefundedEventData::from([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00Z',
        'external_customer_id' => 'customer-123',
        'external_payment_id' => 'invoice-100',
        'external_refund_id' => 'refund-1',
        'refunded_amount_minor' => $amount,
        'currency' => 'RUB',
    ]))->toThrow(InvalidArgumentException::class, 'positive integer');
})->with([0, -1]);

it('matches the finite Linkado event contract values', function (): void {
    expect(array_column(EventType::cases(), 'value'))->toBe([
        'customer_created',
        'lead_created',
        'payment_succeeded',
        'subscription_renewed',
        'payment_refunded',
        'subscription_cancelled',
    ])->and(array_column(PaymentKind::cases(), 'value'))->toBe([
        'subscription_initial',
        'subscription_change',
        'top_up',
        'one_time',
    ])->and(array_column(EventStatus::cases(), 'value'))->toBe([
        'accepted',
        'processing',
        'waiting_dependency',
        'processed',
        'processed_with_warnings',
        'rejected',
        'failed',
    ]);
});
