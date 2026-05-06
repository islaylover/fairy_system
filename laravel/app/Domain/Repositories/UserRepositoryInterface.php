<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\User\User;
use App\Domain\Models\User\UserId;
use App\Domain\Models\User\UserEmail;

interface UserRepositoryInterface
{
    /**
     * ユーザーを全取得して返す
     * 
     * @return User[] ユーザーエンティティの配列
     */
    public function getAll() :array;

    /**
     * IDからユーザーを取得する
     * 
     * @return User|null 見つからない場合はnull
     */
    public function findById(UserId $userId): ?User;

    /**
     * メールアドレスからユーザーを取得する
     * 
     * @return User|null 見つからない場合はnull
     */
    public function findByEmail(UserEmail $userEmail): ?User;

    /**
     * ユーザーを新規作成する
     */
    public function create(User $User): void;

    /**
     * ユーザー情報を更新する
     */
    public function update(User $User):void;

    /**
     * IDを指定してユーザーを削除する
     */
    public function delete(UserId $userId): void;
 
    /**
     * ログイン成たユーザー対してAPIアクセストークンを発行して返す
     *  
     * @param   UserEmail $userEmail ユーザーメールアドレス
     * @param　 string     $tokenName 発行トークン名（デフォルト： api-token）
     * @return  string    発行されたプレーンテキストトークン
     */
    public function issueApiToken(UserEmail $userEmail, string $tokenName = 'api-token'): string;

    /**
     * メールアドレスが既に登録済みか判定する
     */
    public function existsByEmail(UserEmail $email): bool;
}