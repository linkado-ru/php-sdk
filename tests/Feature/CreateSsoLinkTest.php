<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\CreateSsoLinkData;
use Linkado\PhpSdk\DataObjects\SsoLinkData;
use Linkado\PhpSdk\Enums\SsoRedirect;
use Linkado\PhpSdk\LinkadoConnector;
use Linkado\PhpSdk\Requests\CreateSsoLinkRequest;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('sends the exact SSO request and omits a nullable email', function (): void {
    $data = CreateSsoLinkData::from([
        'program_key' => 'program-public-key',
        'external_user_id' => 'hey-user-123',
        'email' => null,
        'email_verified' => false,
        'display_name' => 'Partner Name',
        'redirect_to' => 'affiliate_portal',
    ]);
    $mockClient = new MockClient([
        CreateSsoLinkRequest::class => MockResponse::make(ssoResponseFixture(), 201),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    $connector->ssoLinks()->create($data);

    $mockClient->assertSent(fn (Request $request): bool => $request instanceof CreateSsoLinkRequest
        && $request->getMethod() === Method::POST
        && $request->resolveEndpoint() === 'sso-links'
        && $request->body()->all() === [
            'program_key' => 'program-public-key',
            'external_user_id' => 'hey-user-123',
            'email_verified' => false,
            'display_name' => 'Partner Name',
            'redirect_to' => 'affiliate_portal',
        ]);

    expect($mockClient->getLastPendingRequest()?->getUrl())
        ->toBe('https://linkado.test/api/v1/sso-links')
        ->and($mockClient->getLastPendingRequest()?->headers()->get('Authorization'))
        ->toBe('Bearer integration-credential')
        ->and($mockClient->getLastPendingRequest()?->headers()->get('Accept'))
        ->toBe('application/json')
        ->and($mockClient->getLastPendingRequest()?->headers()->get('Content-Type'))
        ->toBe('application/json');
});

it('sends a verified email and hydrates the signed URL response', function (): void {
    $data = new CreateSsoLinkData(
        program_key: 'program-public-key',
        external_user_id: 'hey-user-123',
        email: 'partner@example.com',
        email_verified: true,
        display_name: 'Partner Name',
        redirect_to: SsoRedirect::AffiliatePortal,
    );
    $mockClient = new MockClient([
        CreateSsoLinkRequest::class => MockResponse::make(ssoResponseFixture(), 201),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);

    $link = $connector->ssoLinks()->create($data);

    expect($link)->toBeInstanceOf(SsoLinkData::class)
        ->and($link->id)->toBe('01K5G8KPFYF3M6D4B7S1NZ8A2Q')
        ->and($link->url)->toBe('https://linkado.test/sso/01K5G8KPFYF3M6D4B7S1NZ8A2Q/one-time-nonce?signature=signed')
        ->and($link->expires_at)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($link->expires_at->format('Y-m-d\TH:i:s.uP'))->toBe('2026-09-19T12:05:00.000000+00:00');

    $mockClient->assertSent(fn (Request $request): bool => $request->body()->all()['email'] === 'partner@example.com'
        && $request->body()->all()['email_verified'] === true);
});

it('rejects verified SSO input without an email before sending', function (): void {
    expect(fn () => CreateSsoLinkData::from([
        'program_key' => 'program-public-key',
        'external_user_id' => 'hey-user-123',
        'email' => null,
        'email_verified' => true,
        'display_name' => 'Partner Name',
        'redirect_to' => 'affiliate_portal',
    ]))->toThrow(InvalidArgumentException::class, 'email is required when email_verified is true');
});

it('rejects arbitrary SSO fields and redirect URLs', function (): void {
    expect(fn () => CreateSsoLinkData::from([
        'program_key' => 'program-public-key',
        'external_user_id' => 'hey-user-123',
        'email' => null,
        'email_verified' => false,
        'display_name' => 'Partner Name',
        'redirect_to' => 'affiliate_portal',
        'user_id' => 123,
    ]))->toThrow(InvalidArgumentException::class, 'Unknown parameter: user_id');

    expect(fn () => CreateSsoLinkData::from([
        'program_key' => 'program-public-key',
        'external_user_id' => 'hey-user-123',
        'email' => null,
        'email_verified' => false,
        'display_name' => 'Partner Name',
        'redirect_to' => 'https://evil.example',
    ]))->toThrow(ValueError::class);
});

it('preserves SSO HTTP failures without exposing a credential or nonce', function (int $status): void {
    $mockClient = new MockClient([
        CreateSsoLinkRequest::class => MockResponse::make([
            'error' => [
                'code' => 'safe_error',
                'message' => 'Unable to create an SSO link.',
            ],
        ], $status, $status === 429 ? ['Retry-After' => '9'] : []),
    ]);
    $connector = new LinkadoConnector('integration-credential', 'https://linkado.test/api/v1/');
    $connector->withMockClient($mockClient);
    $data = CreateSsoLinkData::from([
        'program_key' => 'program-public-key',
        'external_user_id' => 'hey-user-123',
        'email' => null,
        'email_verified' => false,
        'display_name' => 'Partner Name',
        'redirect_to' => 'affiliate_portal',
    ]);

    try {
        $connector->ssoLinks()->create($data);
        $this->fail('Expected a Saloon request exception.');
    } catch (RequestException $exception) {
        expect($exception->getStatus())->toBe($status)
            ->and($exception->getMessage())->not->toContain('integration-credential')
            ->and($exception->getMessage())->not->toContain('one-time-nonce');

        $mockClient->assertSentCount(1);
    }
})->with([401, 403, 422, 429, 500]);

/** @return array<string, mixed> */
function ssoResponseFixture(): array
{
    return [
        'data' => [
            'id' => '01K5G8KPFYF3M6D4B7S1NZ8A2Q',
            'url' => 'https://linkado.test/sso/01K5G8KPFYF3M6D4B7S1NZ8A2Q/one-time-nonce?signature=signed',
            'expires_at' => '2026-09-19T12:05:00.000000Z',
        ],
    ];
}
