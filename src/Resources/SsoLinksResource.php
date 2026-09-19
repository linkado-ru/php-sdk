<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\Resources;

use Linkado\PhpSdk\DataObjects\CreateSsoLinkData;
use Linkado\PhpSdk\DataObjects\SsoLinkData;
use Linkado\PhpSdk\Requests\CreateSsoLinkRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\BaseResource;

final class SsoLinksResource extends BaseResource
{
    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function create(CreateSsoLinkData $data): SsoLinkData
    {
        return $this->connector->send(new CreateSsoLinkRequest($data))->dtoOrFail();
    }
}
