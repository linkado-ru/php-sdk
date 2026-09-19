<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Requests;

use Linkado\PhpSdk\DataObjects\EventData;
use Linkado\PhpSdk\DataObjects\EventResponseData;
use Linkado\PhpSdk\LinkadoConnector;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

final class SendEventRequest extends Request implements HasBody
{
    use HasConnector;
    use HasJsonBody;

    protected string $connector = LinkadoConnector::class;

    protected Method $method = Method::POST;

    public function __construct(public readonly EventData $event) {}

    public function resolveEndpoint(): string
    {
        return 'events';
    }

    protected function defaultBody(): array
    {
        return $this->event->toArray();
    }

    public function createDtoFromResponse(Response $response): EventResponseData
    {
        /** @var array<string, mixed> $data */
        $data = $response->json('data');

        return EventResponseData::fromSaloon($data);
    }
}
