<?php

declare(strict_types=1);

namespace App\Domain\Models\RequestLog;

use App\Domain\Request\RequestId;

readonly class RequestLog {

    public function __construct(
        public RequestId $request_id,
        public RequestLogRole $role,
        public RequestLogMessage $message,
        public ?RequestLogId $id = null
    ) {}

    public function getId(): ?RequestLogId
    {
        return $this->id;
    }

    public function getRequestId(): RequestId
    {
        return $this->request_id;
    }

    public function getRequestLogRole(): RequestLogRole
    {
        return $this->role;
    }

    public function getRequestLogMessage(): RequestLogMessage
    {
        return $this->message;
    }
}