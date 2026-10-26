<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\ResolveTrackingClickData;
use Linkado\PhpSdk\LinkadoConnector;
use Linkado\PhpSdk\Requests\ResolveTrackingClickRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('resolves authoritative click facts with the integration credential and bounded timeout', function (): void {
    // Arrange
    $mock = new MockClient([ResolveTrackingClickRequest::class => MockResponse::make(['data' => [
        'click_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV', 'program_key' => 'program',
        'referral_slug' => 'partner', 'visit_order' => 42,
        'created_at' => '2026-10-20T08:00:00.000001+00:00', 'expires_at' => '2026-12-19T08:00:00.000001+00:00',
    ]])]);
    $connector = (new LinkadoConnector('synthetic-token', 'https://linkado.test/api/v1'))->withMockClient($mock);

    // Act
    $click = $connector->trackingClicks()->resolve(new ResolveTrackingClickData('program', '01ARZ3NDEKTSV4RRFFQ69G5FAV'));

    // Assert
    expect($click?->visit_order)->toBe(42)
        ->and($click?->created_at->format('u'))->toBe('000001')
        ->and($mock->getLastPendingRequest()?->getUrl())->toBe('https://linkado.test/api/v1/tracking/clicks/resolve')
        ->and($mock->getLastPendingRequest()?->headers()->get('Authorization'))->toBe('Bearer synthetic-token');
    expect($mock->getLastPendingRequest()?->config()->get('connect_timeout'))->toBe(1)
        ->and($mock->getLastPendingRequest()?->config()->get('timeout'))->toBe(2);
    $mock->assertSent(fn ($request): bool => $request->getMethod() === Method::POST
        && $request->body()->all() === ['program_key' => 'program', 'click_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV']);
    $mock->assertSentCount(1);
});

it('returns no candidate for an unavailable click without retrying', function (): void {
    // Arrange
    $mock = new MockClient([ResolveTrackingClickRequest::class => MockResponse::make(['data' => null])]);
    $connector = (new LinkadoConnector('synthetic-token', 'https://linkado.test/api/v1'))->withMockClient($mock);

    // Act & Assert
    expect($connector->trackingClicks()->resolve(new ResolveTrackingClickData('program', '01ARZ3NDEKTSV4RRFFQ69G5FAV')))->toBeNull();
    $mock->assertSentCount(1);
});
