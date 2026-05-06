<?php 

namespace App\Infrastructure\Repositories;

use App\Domain\Models\Request\Request;
use App\Domain\Models\Request\RequestId;
use App\Infrastructure\Mappers\RequestMapper;

use App\Domain\Repositories\RequestRepositoryInterface;
use App\Infrastructure\Eloquent\RequestEloquent;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;
use Log;

class EloquentRequestRepository implements RequestRepositoryInterface
{

    public function getAll(): array
    {
        return RequestEloquent::all()
            ->map(fn($eloquentRequest) => RequestMapper::toDomain($eloquentRequest))
            ->all();
    }

    public function findById(RequestId $id): ?Request
    {
        $eloquentRequest = RequestEloquent::find($id->getValue());
        return $eloquentRequest ? RequestMapper::toDomain($eloquentRequest) : null;
    }

    public function create(Request $request): Request
    {
        $eloquentRequest = new RequestEloquent();
        RequestMapper::fillEloquentFromDomain($eloquentRequest, $request);
        $eloquentRequest->save();

        return RequestMapper::toDomain($eloquentRequest);
    }

    public function update(Request $request): Request
    {
        $eloquentRequest = RequestEloquent::find($request->getId()->getValue());
        if (!$eloquentRequest) {
            throw new RuntimeException('更新対象のデータが存在しません。');
        }
        
        RequestMapper::fillEloquentFromDomain($eloquentRequest, $request);
        $eloquentRequest->save();

        return RequestMapper::toDomain($eloquentRequest);
    }

    public function delete(RequestId $id): void
    {
        RequestEloquent::destroy($id->getValue());
    }

    public function getPaginateByUser(int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        $paginator = RequestEloquent::where('user_id', $userId)
            ->orderByDesc('conversation_id')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn($e) => RequestMapper::toDomain($e))
        );

        return $paginator;
    }

    public function existByUserIdAndConversationId(int $userId, int $conversationId): bool
    {
        return RequestEloquent::where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->exists();
    }

    public function getMaxConversationIdByUserId(int $userId): int
    {
        $max = RequestEloquent::where('user_id', $userId)->max('conversation_id');
        return $max ? (int)$max : 0;
    }

    public function existsByUserIdConversationIdAndStatus(int $userId, int $conversationId, int $status): bool
    {
        return RequestEloquent::query()
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->where('status', $status)
        ->exists();
    }
}