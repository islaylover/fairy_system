<?php

namespace App\Domain\Services;

use App\Domain\Repositories\UserRepositoryInterface;

final class UserDomainService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

}
