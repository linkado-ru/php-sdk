<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\CustomerCreatedEventData;
use Linkado\PhpSdk\LinkadoConnector;
use Linkado\PhpSdk\Requests\SendEventRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

it('preserves HTTP failures for consumer classification without retrying', function (int $status): void {
    $headers = $status === 429 ? ['Retry-After' => '17'] : [];
    $mockClient = new MockClient([
        SendEventRequest::class => MockResponse::make([
            'error' => [
                'code' => $status === 409 ? 'idempotency_conflict' : 'api_error',
                'message' => 'Safe API error.',
            ],
        ], $status, $headers),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    try {
        $connector->events()->send(errorEventFixture());
        $this->fail('Expected a Saloon request exception.');
    } catch (RequestException $exception) {
        expect($exception->getStatus())->toBe($status)
            ->and($exception->getMessage())->not->toContain('integration-credential');

        $mockClient->assertSentCount(1);

        if ($status === 429) {
            expect($exception->getResponse()->headers()->get('Retry-After'))->toBe('17');
        }
    }
})->with([401, 403, 408, 409, 422, 429, 500, 503]);

it('propagates fatal connection failures without retrying or leaking credentials', function (): void {
    $mockClient = new MockClient([
        SendEventRequest::class => MockResponse::make()->throw(
            fn (PendingRequest $request) => new FatalRequestException(
                new RuntimeException('Connection failed.'),
                $request,
            ),
        ),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    try {
        $connector->events()->send(errorEventFixture());
        $this->fail('Expected a fatal request exception.');
    } catch (FatalRequestException $exception) {
        expect($exception->getMessage())->toBe('Connection failed.')
            ->and($exception->getMessage())->not->toContain('integration-credential');
    }
});

function errorEventFixture(): CustomerCreatedEventData
{
    return CustomerCreatedEventData::from([
        'event_id' => '01K5G6XYBN7QPF9G0AJM1T2E3R',
        'program_key' => 'program-public-key',
        'occurred_at' => '2026-08-06T10:00:00Z',
        'external_customer_id' => 'customer-123',
    ]);
}
