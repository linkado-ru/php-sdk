<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\CustomerCreatedEventData;
use Linkado\PhpSdk\DataObjects\EventData;
use Linkado\PhpSdk\DataObjects\EventResponseData;
use Linkado\PhpSdk\DataObjects\LeadCreatedEventData;
use Linkado\PhpSdk\DataObjects\PaymentRefundedEventData;
use Linkado\PhpSdk\DataObjects\PaymentSucceededEventData;
use Linkado\PhpSdk\DataObjects\SubscriptionCancelledEventData;
use Linkado\PhpSdk\DataObjects\SubscriptionRenewedEventData;
use Linkado\PhpSdk\Enums\EventStatus;
use Linkado\PhpSdk\LinkadoConnector;
use Linkado\PhpSdk\Requests\SendEventRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('sends the exact Linkado body for every v1 event type', function (string $type): void {
    $event = eventFixture($type);
    $expected = eventPayloadFixture($type);
    $mockClient = new MockClient([
        SendEventRequest::class => MockResponse::make(eventResponseFixture($expected['event_id']), 202),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    $connector->events()->send($event);

    $mockClient->assertSent(fn (Request $request): bool => $request instanceof SendEventRequest
        && $request->getMethod() === Method::POST
        && $request->resolveEndpoint() === 'events'
        && $request->body()->all() === $expected);

    expect($mockClient->getLastPendingRequest()?->getUrl())
        ->toBe('https://linkado.test/api/v1/events');
})->with([
    'customer_created',
    'lead_created',
    'payment_succeeded',
    'subscription_renewed',
    'payment_refunded',
    'subscription_cancelled',
]);

it('hydrates first and duplicate delivery responses with typed status and diagnostics', function (int $httpStatus): void {
    $event = eventFixture('customer_created');
    $response = [
        'data' => [
            'id' => '01K5G7A5SKZ8F7GJ0ZC05AQ8C2',
            'event_id' => $event->event_id,
            'status' => $httpStatus === 202 ? 'accepted' : 'processed_with_warnings',
            'result' => $httpStatus === 202 ? null : [
                'customer_id' => '01K5G7K99AF0XRTBQE4A4V7N2P',
                'created' => true,
                'attributed' => false,
            ],
            'warnings' => $httpStatus === 202 ? [] : ['attribution_referral_invalid'],
        ],
    ];
    $mockClient = new MockClient([
        SendEventRequest::class => MockResponse::make($response, $httpStatus),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    $result = $connector->events()->send($event);

    expect($result)->toBeInstanceOf(EventResponseData::class)
        ->and($result->id)->toBe('01K5G7A5SKZ8F7GJ0ZC05AQ8C2')
        ->and($result->event_id)->toBe($event->event_id)
        ->and($result->status)->toBe($httpStatus === 202
            ? EventStatus::Accepted
            : EventStatus::ProcessedWithWarnings)
        ->and($result->result)->toBe($response['data']['result'])
        ->and($result->warnings)->toBe($response['data']['warnings']);
})->with([
    'first delivery' => 202,
    'identical duplicate' => 200,
]);

it('keeps the same event ID and byte-equivalent body across repeated sends', function (): void {
    $event = eventFixture('payment_refunded');
    $expected = eventPayloadFixture('payment_refunded');
    $mockClient = new MockClient([
        SendEventRequest::class => MockResponse::make(eventResponseFixture($event->event_id), 202),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    $connector->events()->send($event);
    $connector->events()->send($event);

    $responses = $mockClient->getRecordedResponses();

    expect($responses)->toHaveCount(2)
        ->and($responses[0]->getPendingRequest()->body()?->all())->toBe($expected)
        ->and($responses[1]->getPendingRequest()->body()?->all())->toBe($expected)
        ->and($responses[1]->getPendingRequest()->body()?->all()['event_id'])->toBe($event->event_id);
});

/** @return array<string, mixed> */
function eventResponseFixture(string $eventId): array
{
    return [
        'data' => [
            'id' => '01K5G7A5SKZ8F7GJ0ZC05AQ8C2',
            'event_id' => $eventId,
            'status' => 'accepted',
            'result' => null,
            'warnings' => [],
        ],
    ];
}

function eventFixture(string $type): EventData
{
    $common = [
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => new DateTimeImmutable('2026-08-06T13:00:00+03:00'),
        'external_customer_id' => 'customer-123',
    ];

    return match ($type) {
        'customer_created' => CustomerCreatedEventData::from([
            ...$common,
            'click_id' => '01K5G70W42MJJVP4GQJ3A3RN01',
            'metadata' => [
                'source' => 'hey-ai',
                'source_event' => 'user.registered',
            ],
        ]),
        'lead_created' => LeadCreatedEventData::from([
            ...$common,
            'referral_slug' => 'partner-one',
        ]),
        'payment_succeeded' => PaymentSucceededEventData::from([
            ...$common,
            'external_payment_id' => 'invoice-100',
            'amount_minor' => 159900,
            'currency' => 'RUB',
            'payment_kind' => 'subscription_initial',
            'external_subscription_id' => 'subscription-10',
        ]),
        'subscription_renewed' => SubscriptionRenewedEventData::from([
            ...$common,
            'external_payment_id' => 'invoice-101',
            'external_subscription_id' => 'subscription-10',
            'amount_minor' => 159900,
            'currency' => 'RUB',
        ]),
        'payment_refunded' => PaymentRefundedEventData::from([
            ...$common,
            'external_payment_id' => 'invoice-101',
            'external_refund_id' => 'refund-delta-1',
            'refunded_amount_minor' => 50000,
            'currency' => 'RUB',
        ]),
        'subscription_cancelled' => SubscriptionCancelledEventData::from([
            ...$common,
            'external_subscription_id' => 'subscription-10',
        ]),
    };
}

/** @return array<string, mixed> */
function eventPayloadFixture(string $type): array
{
    $common = [
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'type' => $type,
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00+00:00',
        'external_customer_id' => 'customer-123',
    ];

    return match ($type) {
        'customer_created' => [
            ...$common,
            'click_id' => '01K5G70W42MJJVP4GQJ3A3RN01',
            'metadata' => [
                'source' => 'hey-ai',
                'source_event' => 'user.registered',
            ],
        ],
        'lead_created' => [...$common, 'referral_slug' => 'partner-one'],
        'payment_succeeded' => [
            ...$common,
            'external_payment_id' => 'invoice-100',
            'amount_minor' => 159900,
            'currency' => 'RUB',
            'payment_kind' => 'subscription_initial',
            'external_subscription_id' => 'subscription-10',
        ],
        'subscription_renewed' => [
            ...$common,
            'external_payment_id' => 'invoice-101',
            'external_subscription_id' => 'subscription-10',
            'amount_minor' => 159900,
            'currency' => 'RUB',
        ],
        'payment_refunded' => [
            ...$common,
            'external_payment_id' => 'invoice-101',
            'external_refund_id' => 'refund-delta-1',
            'refunded_amount_minor' => 50000,
            'currency' => 'RUB',
        ],
        'subscription_cancelled' => [
            ...$common,
            'external_subscription_id' => 'subscription-10',
        ],
    };
}
