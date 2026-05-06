<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\RequestLog\RequestLog;
use App\Domain\Models\RequestLog\RequestLogId;

interface RequestLogRepositoryInterface
{
    public function getAll() :array;
    public function findById(RequestLogId $requestLogId): ?RequestLog;
    public function create(RequestLog $RequestLog): bool;
    public function update(RequestLog $RequestLog):bool;
    public function delete(RequestLogId $requestLogId) :bool;
}