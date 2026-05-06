<?php

namespace App\Domain\Services;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Models\User\UserEmail;

final class UserDomainService
{

    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}
    
}