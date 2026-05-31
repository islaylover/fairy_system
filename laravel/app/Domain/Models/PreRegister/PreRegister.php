<?php

declare(strict_types=1);

namespace App\Domain\Models\PreRegister;

readonly class PreRegister
{
    public function __construct(
        public PreRegisterEmail $email,
        public PreRegisterToken $token,
        public PreRegisterExpiresAt $expires_at,
        public ?PreRegisterId $id = null
    ) {}

    public function getId(): ?PreRegisterId
    {
        return $this->id;
    }

    public function getEmail(): PreRegisterEmail
    {
        return $this->email;
    }

    public function getToken(): PreRegisterToken
    {
        return $this->token;
    }

    public function getExpiresAt(): PreRegisterExpiresAt
    {
        return $this->expires_at;
    }
}
