<?php

namespace App\Http\Controllers\Refactor\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Refactor\User\AddFriendRequest;
use App\Http\Resources\Refactor\User\UserFriendResource;
use App\Repositories\Interfaces\UserFriendRepositoryInterface;

class UserFriendController extends Controller
{
    protected UserFriendRepositoryInterface $repo;

    public function __construct(UserFriendRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {

        return UserFriendResource::collection($this->repo->list());
    }

    public function following()
    {
        return ($this->repo->following());
    }

    public function store(AddFriendRequest $request)
    {
        $result = $this->repo->add($request->friend_id);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        return new UserFriendResource($result['data']);
    }

    public function destroy($friendId)
    {
        $result = $this->repo->remove($friendId);

        return response()->json(['deleted' => $result['deleted']]);
    }
}
