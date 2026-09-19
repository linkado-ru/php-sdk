<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Tests\Unit;

use InvalidArgumentException;
use Linkado\PhpSdk\Concerns\Data;

it('hydrates and serializes backed enums from raw arrays', function (): void {
    $prototype = new class(DataFixtureState::Pending) extends Data
    {
        public function __construct(
            public readonly DataFixtureState $status,
        ) {}
    };

    $class = $prototype::class;
    $data = $class::from(['status' => 'complete']);

    expect($data->status)->toBe(DataFixtureState::Complete)
        ->and($data->toArray())->toBe(['status' => 'complete']);
});

it('rejects unknown raw array keys instead of silently ignoring them', function (): void {
    $prototype = new class('known') extends Data
    {
        public function __construct(
            public readonly string $known,
        ) {}
    };

    $class = $prototype::class;

    expect(fn () => $class::from([
        'known' => 'value',
        'unexpected' => 'secret',
    ]))->toThrow(InvalidArgumentException::class, 'Unknown parameter: unexpected');
});

it('fails when a required constructor parameter is missing', function (): void {
    $prototype = new class('required') extends Data
    {
        public function __construct(
            public readonly string $required,
        ) {}
    };

    $class = $prototype::class;

    expect(fn () => $class::from())->toThrow(
        InvalidArgumentException::class,
        'Missing required parameter: required',
    );
});
