<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Models\Request\RequestId;
use App\Domain\Models\RequestLog\RequestLog;
use App\Domain\Models\RequestLog\RequestLogId;
use App\Domain\Models\RequestLog\RequestLogMessage;
use App\Domain\Models\RequestLog\RequestLogRole;
use App\Domain\Repositories\RequestLogRepositoryInterface;
use App\Infrastructure\Eloquent\RequestLogEloquent;
use Illuminate\Support\Collection;

class EloquentRequestLogRepository implements RequestLogRepositoryInterface
{
    public function getAll(): array
    {
        return RequestLogEloquent::all()->map(function ($eloquentRequestLog) {
            return new RequestLog(
                new RequestLogId($eloquentRequestLog->id),
                new RequestId($eloquentRequestLog->request_id),
                new RequestLogRole($eloquentRequestLog->role),
                new RequestLogMessage($eloquentRequestLog->message)
            );
        })->all(); // all() : convert result(collection) to array
    }

    public function findById(RequestLogId $id): ?RequestLog
    {
        $eloquentRequestLog = RequestLogEloquent::find($id->getValue());
        if (! $eloquentRequestLog) {
            return null;
        }

        return new RequestLog(
            new RequestLogId($eloquentRequestLog->id),
            new RequestId($eloquentRequestLog->request_id),
            new RequestLogRole($eloquentRequestLog->role),
            new RequestLogMessage($eloquentRequestLog->message)
        );
    }

    public function create(RequestLog $RequestLog): bool
    {
        $eloquentRequestLog = new RequestLogEloquent;
        $eloquentRequestLog->request_id = $RequestLog->getRequestId()->getValue();
        $eloquentRequestLog->role = $RequestLog->getRequestLogRole()->getValue();
        $eloquentRequestLog->message = $RequestLog->getRequestLogMessage()->getValue();

        return $eloquentRequestLog->save();
    }

    public function update(RequestLog $RequestLog): bool
    {
        $eloquentRequestLog = RequestLogEloquent::find($RequestLog->getId()->getValue());
        if (! $eloquentRequestLog) {
            return false;
        }
        $eloquentRequestLog->request_id = $RequestLog->getRequestId()->getValue();
        $eloquentRequestLog->role = $RequestLog->getRequestLogRole()->getValue();
        $eloquentRequestLog->message = $RequestLog->getRequestLogMessage()->getValue();

        return $eloquentRequestLog->save();
    }

    public function delete(RequestLogId $id): bool
    {
        return RequestLogEloquent::destroy($id->getValue());
    }
}
