<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\PreRegister\PreRegister;
use App\Domain\Models\PreRegister\PreRegisterId;
use App\Domain\Models\PreRegister\PreRegisterToken;

interface PreRegisterRepositoryInterface
{
    /**
     * 全仮登録者を取得して返す
     * 
     * @return PreRegister[] 仮登録者エンティティの配列
     */
    public function getAll() :array;

    /**
     * IDから仮登録レコードを取得する
     * 
     * @return PreRegister|null 見つからない場合はnull
     */
    public function findById(PreRegisterId $preRegisterId): ?PreRegister;

    /**
     * トークンから仮登録レコードを取得する
     * 
     * @return PreRegister|null 見つからない場合はnull
     */
    public function findByToken(PreRegisterToken $token): ?PreRegister;

    /**
     * 仮登録レコードを新規作成する
     */
    public function create(PreRegister $PreRegister): void;

    /**
     * IDを指定して仮登録レコードを削除する
     */
    public function delete(PreRegisterId $id): void;
}