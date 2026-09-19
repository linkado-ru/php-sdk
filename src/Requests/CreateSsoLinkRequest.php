<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Requests;

use Linkado\PhpSdk\DataObjects\CreateSsoLinkData;
use Linkado\PhpSdk\DataObjects\SsoLinkData;
use Linkado\PhpSdk\LinkadoConnector;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Request\HasConnector;

final class CreateSsoLinkRequest extends Request implements HasBody
{
    use HasConnector;
    use HasJsonBody;

    protected string $connector = LinkadoConnector::class;

    protected Method $method = Method::POST;

    public function __construct(public readonly CreateSsoLinkData $data) {}

    public function resolveEndpoint(): string
    {
        return 'sso-links';
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): SsoLinkData
    {
        /** @var array<string, mixed> $data */
        $data = $response->json('data');

        return SsoLinkData::fromSaloon($data);
    }
}
