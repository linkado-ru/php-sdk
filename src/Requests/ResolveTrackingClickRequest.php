<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Requests;

use Linkado\PhpSdk\DataObjects\ResolveTrackingClickData;
use Linkado\PhpSdk\DataObjects\TrackingClickData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

final class ResolveTrackingClickRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(public readonly ResolveTrackingClickData $data) {}

    public function resolveEndpoint(): string
    {
        return 'tracking/clicks/resolve';
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    protected function defaultConfig(): array
    {
        return ['connect_timeout' => 1, 'timeout' => 2];
    }

    public function createDtoFromResponse(Response $response): ?TrackingClickData
    {
        /** @var array<string, mixed>|null $data */
        $data = $response->json('data');

        return $data === null ? null : TrackingClickData::fromSaloon($data);
    }
}
