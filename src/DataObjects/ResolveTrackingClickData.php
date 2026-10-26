<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use Linkado\PhpSdk\Concerns\Data;

final class ResolveTrackingClickData extends Data
{
    public function __construct(
        public readonly string $program_key,
        public readonly string $click_id,
    ) {}
}
