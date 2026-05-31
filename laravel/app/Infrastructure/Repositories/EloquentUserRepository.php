<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Models\User\User;
use App\Domain\Models\User\UserEmail;
use App\Domain\Models\User\UserId;
use App\Domain\Models\User\UserName;
use App\Domain\Models\User\UserPassword;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Eloquent\UserEloquent;
use Illuminate\Support\Collection;
use RuntimeException;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getAll(): array
    {
        return UserEloquent::all()->map(function ($eloquentUser) {
            return new User(
                new UserName($eloquentUser->name),
                new UserEmail($eloquentUser->email),
                new UserPassword($eloquentUser->password),
                new UserId($eloquentUser->id)
            );
        })->all(); // all() : convert result(collection) to array
    }

    public function findById(UserId $id): ?User
    {
        $eloquentUser = UserEloquent::find($id->getValue());
        if (! $eloquentUser) {
            return null;
        }

        return new User(
            new UserName($eloquentUser->name),
            new UserEmail($eloquentUser->email),
            new UserPassword($eloquentUser->password),
            new UserId($eloquentUser->id)
        );
    }

    public function findByEmail(UserEmail $email): ?User
    {
        $eloquentUser = UserEloquent::where('email', $email->getValue())->first();

        if (! $eloquentUser) {
            return null;
        }

        return new User(
            new UserName($eloquentUser->name),
            new UserEmail($eloquentUser->email),
            new UserPassword($eloquentUser->password),
            new UserId($eloquentUser->id)
        );
    }

    public function create(User $User): void
    {
        $eloquentUser = new UserEloquent;
        $eloquentUser->name = $User->getName()->getValue();
        $eloquentUser->email = $User->getEmail()->getValue();
        $eloquentUser->password = $User->getPassword()->getValue();
        $eloquentUser->save();
    }

    public function update(User $User): void
    {
        $eloquentUser = UserEloquent::find($User->getId()->getValue());
        if (! $eloquentUser) {
            throw new RuntimeException('User not found');
        }
        $eloquentUser->name = $User->getName()->getValue();
        $eloquentUser->email = $User->getEmail()->getValue();
        $eloquentUser->password = $User->getPassword()->getValue();
        $eloquentUser->save();
    }

    public function delete(UserId $id): void
    {
        UserEloquent::destroy($id->getValue());
    }

    public function issueApiToken(UserEmail $userEmail, string $tokenName = 'api-token'): string
    {
        $userEloquent = UserEloquent::where('email', $userEmail->getValue())->firstOrFail();
        // 既存api token削除
        $userEloquent->tokens()->delete();
        // 新しいapi token発行
        $apiToken = $userEloquent->createToken($tokenName)->plainTextToken;

        return $apiToken;
    }

    public function existsByEmail(UserEmail $email): bool
    {
        return UserEloquent::where('email', $email->getValue())->exists();
    }
}
