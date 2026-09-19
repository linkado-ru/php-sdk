<?php

declare(strict_types=1);

namespace Linkado\PhpSdk;

use Closure;
use Linkado\PhpSdk\Resources\EventsResource;
use Linkado\PhpSdk\Resources\SsoLinksResource;
use LogicException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use SensitiveParameter;

final class LinkadoConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    /** @var Closure(): string */
    private readonly Closure $tokenResolver;

    private readonly string $baseUrl;

    public function __construct(
        #[SensitiveParameter]
        string $token,
        string $baseUrl,
    ) {
        $this->tokenResolver = static fn (): string => $token;
        $this->baseUrl = rtrim($baseUrl, '/').'/';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator(($this->tokenResolver)());
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function events(): EventsResource
    {
        return new EventsResource($this);
    }

    public function ssoLinks(): SsoLinksResource
    {
        return new SsoLinksResource($this);
    }

    /** @return array{baseUrl: string} */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl];
    }

    /** @return never */
    public function __serialize(): array
    {
        throw new LogicException('LinkadoConnector instances cannot be serialized.');
    }
}
