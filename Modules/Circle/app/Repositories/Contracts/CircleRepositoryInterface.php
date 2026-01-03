<?php
// Modules/Circle/Repositories/Contracts/CircleRepositoryInterface.php

namespace Modules\Circle\Repositories\Contracts;

use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

interface CircleRepositoryInterface extends BaseRepositoryInterface
{
    public function createCircle(array $data, int $creatorId);
    public function getUserCircles(int $userId, array $filters = []);
    public function getCircleWithMembers(int $circleId);
    public function findActiveCircleByUser(int $userId, int $circleId);
}
