<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Tests\Unit;

enum DataFixtureState: string
{
    case Pending = 'pending';
    case Complete = 'complete';
}
