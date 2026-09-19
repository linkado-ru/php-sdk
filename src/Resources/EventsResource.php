<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Resources;

use Linkado\PhpSdk\DataObjects\EventData;
use Linkado\PhpSdk\DataObjects\EventResponseData;
use Linkado\PhpSdk\Requests\SendEventRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\BaseResource;

final class EventsResource extends BaseResource
{
    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function send(EventData $event): EventResponseData
    {
        return $this->connector->send(new SendEventRequest($event))->dtoOrFail();
    }
}
