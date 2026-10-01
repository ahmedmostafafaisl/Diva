<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface FollowRequestRepositoryInterface
{
    public function sendRequest(User $user): array;
    public function respondToRequest(User $user, string $status): array;
    public function myFollowRequests(): array;
}
