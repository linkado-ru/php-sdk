<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Enums;

enum EventStatus: string
{
    case Accepted = 'accepted';
    case Processing = 'processing';
    case WaitingDependency = 'waiting_dependency';
    case Processed = 'processed';
    case ProcessedWithWarnings = 'processed_with_warnings';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
