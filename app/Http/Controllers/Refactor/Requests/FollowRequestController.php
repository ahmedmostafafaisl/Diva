<?php

namespace App\Http\Controllers\Refactor\Requests;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Refactor\Requests\RespondToFollowRequest;
use App\Http\Resources\Refactor\Requests\FollowRequestResource;
use App\Repositories\Interfaces\FollowRequestRepositoryInterface;

class FollowRequestController extends Controller
{
    protected FollowRequestRepositoryInterface $repo;

    public function __construct(FollowRequestRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function sendRequest(User $user)
    {
        $result = $this->repo->sendRequest($user);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return new FollowRequestResource($result['data']);
    }

    public function respond(User $user, RespondToFollowRequest $request)
    {
        $result = $this->repo->respondToRequest($user, $request->status);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 404);
        }

        return new FollowRequestResource($result['data']);
    }

    public function myFollowRequests()
    {
        $requests = $this->repo->myFollowRequests();

        return response()->json([
            'sent' => FollowRequestResource::collection($requests['sent']),
            'received' => FollowRequestResource::collection($requests['received']),
        ]);
    }
}
