<?php

namespace App\Repositories\Interfaces;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Notification;
    public function create(array $data): Notification;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function getUserNotifications();
}
