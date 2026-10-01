<?php

namespace App\Repositories\Requests;

use App\Models\User;
use App\Models\UserFriend;
use App\Models\FollowRequest;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\FollowRequestRepositoryInterface;

class FollowRequestRepository implements FollowRequestRepositoryInterface
{
    public function sendRequest(User $user): array
    {
        $authUser = auth()->user();

        if ($authUser->id === $user->id) {
            return ['error' => 'You cannot follow yourself.'];
        }

        $status = $user->is_private ? 'pending' : 'accepted';

        $request = FollowRequest::updateOrCreate(
            [
                'follower_id' => $authUser->id,
                'followed_id' => $user->id,
            ],
            [
                'status' => $status
            ]
        );

        // If the user is public, auto-create friend entry
        if ($status === 'accepted') {
            UserFriend::firstOrCreate(['user_id' => Auth::id(), 'friend_id' => $user->id]);
            UserFriend::firstOrCreate(['user_id' => $user->id, 'friend_id' => Auth::id()]);
        }


        return ['data' => $request];
    }


    public function respondToRequest(User $user, string $status): array
    {
        $request = FollowRequest::where('follower_id', $user->id)
            ->where('followed_id', Auth::id())
            ->first();

        if (!$request) {
            return ['error' => 'No follow request found.'];
        }

        $request->update(['status' => $status]);

        if ($status === 'accepted') {
            UserFriend::firstOrCreate(['user_id' => Auth::id(), 'friend_id' => $user->id]);
            UserFriend::firstOrCreate(['user_id' => $user->id, 'friend_id' => Auth::id()]);
        }


        return ['data' => $request];
    }

    public function myFollowRequests(): array
    {
        $user = Auth::user();

        return [
            'received' => $user->followRequestsReceived()->with('follower')->get(),
            'sent' => $user->followRequestsSent()->with('followed')->get()
        ];
    }
}
