<?php
// Modules/Circle/Repositories/Eloquent/CircleRepository.php

namespace Modules\Circle\Repositories;

use Modules\Circle\Models\Circle;
use Modules\Circle\Models\CircleMember;
use Modules\Circle\Repositories\Contracts\CircleRepositoryInterface;
use Modules\Core\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CircleRepository extends CoreRepository implements CircleRepositoryInterface
{
    protected Model $model;

    public function __construct(Circle $circle)
    {
        $this->model = $circle;
    }

    public function createCircle(array $data, int $creatorId): Circle
    {
        do {
            $code = 'Circle_' . strtoupper(Str::random(8));
        } while ($this->model->where('code',$code)->exists());

        $circle = $this->create(array_merge($data, [
            'creator_id' => $creatorId,
            'status' => 'pending',
            'code' => $code
        ]));
        // Add creator as first member
        $circle->members()->create([
            'user_id' => $creatorId,
            'position' => $data['positioning_method'] == 'sequence' ? 1 : 0,
        ]);

        return $circle;
    }

    public function getUserCircles(int $userId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model
            ->whereHas('members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['creator', 'members.user'])
            ->latest();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getCircleWithMembers(int $circleId): Circle
    {
        return $this->model
            ->with(['creator', 'members.user'])
            ->findOrFail($circleId);
    }

    public function findActiveCircleByUser(int $userId, int $circleId): ?Circle
    {
        return $this->model
            ->where('id', $circleId)
            ->where('status', 'active')
            ->whereHas('members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->first();
    }
}
