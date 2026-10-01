<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\UserFriend;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\UserFriendRepositoryInterface;

class UserFriendRepository implements UserFriendRepositoryInterface
{
    public function list()
    {
        return UserFriend::where('user_id', Auth::id())->with('friend')->get();
    }

    public function following()
    {

        return UserFriend::where('friend_id', Auth::id())
            ->with(['user' => function ($query) {
                $query->select('id', 'username', 'email', 'phone');
            }])
            ->get()
            ->map(function ($friend) {
                return [
                    'id' => $friend->user->id,
                    'username' => $friend->user->username,
                    'email' => $friend->user->email,
                    'phone' => $friend->user->phone,
                    'added_at' => $friend->created_at->toDateTimeString(),
                ];
            });
    }
    public function add(int $friendId)
    {
        if ($friendId === Auth::id()) {
            return ['error' => 'You cannot add yourself as a friend.'];
        }

        $user = User::find($friendId);

        if (!$user) {
            return ['error' => 'User not found.'];
        }

        if ($user->is_private) {
            return ['error' => 'This user has a private profile and must approve your request.'];
        }

        UserFriend::firstOrCreate(['user_id' => Auth::id(), 'friend_id' => $user->id]);

        return ['message' => 'Friendship added successfully.'];
    }

    public function remove(int $friendId)
    {
        $deleted = UserFriend::where('user_id', Auth::id())
            ->where('friend_id', $friendId)
            ->delete();

        return ['deleted' => $deleted];
    }
}
