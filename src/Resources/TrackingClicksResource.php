<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Resources;

use Linkado\PhpSdk\DataObjects\ResolveTrackingClickData;
use Linkado\PhpSdk\DataObjects\TrackingClickData;
use Linkado\PhpSdk\Requests\ResolveTrackingClickRequest;
use Saloon\Http\BaseResource;

final class TrackingClicksResource extends BaseResource
{
    public function resolve(ResolveTrackingClickData $data): ?TrackingClickData
    {
        return $this->connector->send(new ResolveTrackingClickRequest($data))->dto();
    }
}
