<?php

declare(strict_types=1);

use Linkado\PhpSdk\LinkadoConnector;
use Linkado\PhpSdk\Resources\EventsResource;
use Linkado\PhpSdk\Resources\SsoLinksResource;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('requires and normalizes a custom API base URL', function (): void {
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1');

    expect($connector->resolveBaseUrl())->toBe('https://linkado.test/api/v1/');
});

it('adds bearer authentication and JSON accept headers', function (): void {
    $mockClient = new MockClient([MockResponse::make(['ok' => true])]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    $connector->send(new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return 'probe';
        }
    });

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest)->not->toBeNull()
        ->and($pendingRequest->headers()->get('Authorization'))->toBe('Bearer integration-credential')
        ->and($pendingRequest->headers()->get('Accept'))->toBe('application/json')
        ->and($pendingRequest->getUrl())->toBe('https://linkado.test/api/v1/probe');
});

it('exposes typed event and SSO resources', function (): void {
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');

    expect($connector->events())->toBeInstanceOf(EventsResource::class)
        ->and($connector->ssoLinks())->toBeInstanceOf(SsoLinksResource::class);
});

it('does not expose the credential through debug or serialization output', function (): void {
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');

    expect(var_export($connector, true))->not->toContain('integration-credential')
        ->and(fn () => serialize($connector))->toThrow(
            LogicException::class,
            'LinkadoConnector instances cannot be serialized.',
        );
});
