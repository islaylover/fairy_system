<?php

declare(strict_types=1);

namespace App\Domain\Models\User;

readonly class User {

    public function __construct(
        public UserName $name,
        public UserEmail $email,
        public UserPassword $password,
        public ?UserId $id = null
    ) {}

    public function getId(): ?UserId
    {
        return $this->id;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

        public function getPassword(): UserPassword
    {
        return $this->password;
    }
}