<?php

declare(strict_types=1);

namespace Linkado\PhpSdk\DataObjects;

use InvalidArgumentException;
use Linkado\PhpSdk\Concerns\Data;
use Linkado\PhpSdk\Enums\SsoRedirect;

final class CreateSsoLinkData extends Data
{
    public function __construct(
        public readonly string $program_key,
        public readonly string $external_user_id,
        public readonly ?string $email,
        public readonly bool $email_verified,
        public readonly string $display_name,
        public readonly SsoRedirect $redirect_to,
    ) {
        if ($email_verified && $email === null) {
            throw new InvalidArgumentException('email is required when email_verified is true');
        }
    }

    /** @return array<string, bool|string> */
    public function toArray(): array
    {
        $data = [
            'program_key' => $this->program_key,
            'external_user_id' => $this->external_user_id,
        ];

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        $data['email_verified'] = $this->email_verified;
        $data['display_name'] = $this->display_name;
        $data['redirect_to'] = $this->redirect_to->value;

        return $data;
    }
}
