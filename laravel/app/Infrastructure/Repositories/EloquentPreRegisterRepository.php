<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Models\PreRegister\PreRegister;
use App\Domain\Models\PreRegister\PreRegisterEmail;
use App\Domain\Models\PreRegister\PreRegisterExpiresAt;
use App\Domain\Models\PreRegister\PreRegisterId;
use App\Domain\Models\PreRegister\PreRegisterToken;
use App\Domain\Repositories\PreRegisterRepositoryInterface;
use App\Infrastructure\Eloquent\PreRegisterEloquent;
use Illuminate\Support\Collection;

class EloquentPreRegisterRepository implements PreRegisterRepositoryInterface
{
    public function getAll(): array
    {
        return PreRegisterEloquent::all()->map(function ($eloquentPreRegister) {
            return new PreRegister(
                new PreRegisterEmail($eloquentPreRegister->email),
                new PreRegisterToken($eloquentPreRegister->token),
                new PreRegisterExpiresAt($eloquentPreRegister->expires_at),
                new PreRegisterId($eloquentPreRegister->id)
            );
        })->all(); // all() : convert result(collection) to array
    }

    public function findById(PreRegisterId $id): ?PreRegister
    {
        $eloquentPreRegister = PreRegisterEloquent::find($id->getValue());
        if (! $eloquentPreRegister) {
            return null;
        }

        return new PreRegister(
            new PreRegisterEmail($eloquentPreRegister->email),
            new PreRegisterToken($eloquentPreRegister->token),
            new PreRegisterExpiresAt($eloquentPreRegister->expires_at),
            new PreRegisterId($eloquentPreRegister->id)
        );
    }

    public function findByToken(PreRegisterToken $token): ?PreRegister
    {
        $eloquentPreRegister = PreRegisterEloquent::where('token', $token->getValue())
            ->where('expires_at', '>=', now())
            ->first();
        if (! $eloquentPreRegister) {
            return null;
        }

        return new PreRegister(
            new PreRegisterEmail($eloquentPreRegister->email),
            new PreRegisterToken($eloquentPreRegister->token),
            new PreRegisterExpiresAt($eloquentPreRegister->expires_at),
            new PreRegisterId($eloquentPreRegister->id)
        );
    }

    public function create(PreRegister $PreRegister): void
    {
        $eloquentPreRegister = new PreRegisterEloquent;
        $eloquentPreRegister->email = $PreRegister->getEmail()->getValue();
        $eloquentPreRegister->token = $PreRegister->getToken()->getValue();
        $eloquentPreRegister->expires_at = $PreRegister->getExpiresAt()->getValue();
        $eloquentPreRegister->save();
    }

    public function delete(PreRegisterId $id): void
    {
        PreRegisterEloquent::destroy($id->getValue());
    }
}
