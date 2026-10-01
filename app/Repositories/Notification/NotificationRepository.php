<?php

namespace App\Repositories\Notification;

use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function all(): Collection
    {
        return Notification::all();
    }

    public function find(int $id): ?Notification
    {
        return Notification::find($id);
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Notification::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Notification::destroy($id) > 0;
    }
    public function getUserNotifications()
    {
        $user = auth()->user();
        return  $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
