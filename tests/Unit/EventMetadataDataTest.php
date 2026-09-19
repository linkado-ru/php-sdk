<?php

declare(strict_types=1);

use Linkado\PhpSdk\DataObjects\EventMetadataData;

it('serializes allowlisted scalar metadata without null fields', function (): void {
    $metadata = EventMetadataData::from([
        'source' => 'hey-ai',
        'source_event' => '',
        'plan_code' => 0,
        'billing_reason' => false,
    ]);

    expect($metadata->toArray())->toBe([
        'source' => 'hey-ai',
        'source_event' => '',
        'plan_code' => 0,
        'billing_reason' => false,
    ]);
});

it('rejects common prohibited PII shapes in metadata', function (string|int $value): void {
    expect(fn () => EventMetadataData::from(['source' => $value]))
        ->toThrow(InvalidArgumentException::class, 'must not contain credentials or PII');
})->with([
    'email' => 'customer@example.com',
    'phone' => '+7 999 123-45-67',
    'tax identifier' => 7707083893,
    'card' => '4111111111111111',
    'iban' => 'GB82WEST12345698765432',
    'bearer credential' => 'Bearer lk_secret_value',
    'ip address' => '192.0.2.10',
]);

it('rejects metadata larger than 4096 canonical JSON bytes', function (): void {
    expect(fn () => EventMetadataData::from([
        'source' => str_repeat('a', 4090),
    ]))->toThrow(InvalidArgumentException::class, 'must not exceed 4096');
});

it('measures the metadata limit in UTF-8 bytes rather than characters', function (): void {
    expect(fn () => EventMetadataData::from([
        'source' => str_repeat('é', 2043),
    ]))->toThrow(InvalidArgumentException::class, 'must not exceed 4096');
});
