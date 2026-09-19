<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use Linkado\PhpSdk\Enums\EventType;

final class CustomerCreatedEventData extends AttributionEventData
{
    public function type(): EventType
    {
        return EventType::CustomerCreated;
    }
}
