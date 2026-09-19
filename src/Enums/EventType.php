<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Enums;

enum EventType: string
{
    case CustomerCreated = 'customer_created';
    case LeadCreated = 'lead_created';
    case PaymentSucceeded = 'payment_succeeded';
    case SubscriptionRenewed = 'subscription_renewed';
    case PaymentRefunded = 'payment_refunded';
    case SubscriptionCancelled = 'subscription_cancelled';
}
