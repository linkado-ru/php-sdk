<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Enums;

enum PaymentKind: string
{
    case SubscriptionInitial = 'subscription_initial';
    case SubscriptionChange = 'subscription_change';
    case TopUp = 'top_up';
    case OneTime = 'one_time';
}
