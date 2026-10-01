<?php

namespace App\Http\Controllers\Refactor\User;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Refactor\User\UserDataResource;
use App\Http\Requests\Refactor\User\StoreUserDataRequest;
use App\Repositories\Interfaces\UserDataRepositoryInterface;

class UserDataController extends Controller
{
    use ApiResponseHelper;
    protected $repository;

    public function __construct(UserDataRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return UserDataResource::collection($this->repository->all());
    }

    public function store(StoreUserDataRequest $request)
    {

        $data = $request->validated();
        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }
        $data['user_id'] = Auth::id();
        $user = auth()->user();
        $userData = $this->repository->create($data, $user);
        return $this->setCode(code: 200)->setData(($userData))->setMessage('success')->send();

        $existing = $this->repository->getByUserId(Auth::id());
        if ($existing) {
            return response()->json(['message' => 'User data already exists. Use update.'], 409);
        }

        return $this->setCode(code: 200)->setData(new UserDataResource($userData))->setMessage('success')->send();
    }

    public function show()
    {
        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }
        $userData = $this->repository->getByUserId(auth()->user());
        return $this->setCode(code: 200)->setData(($userData))->setMessage('success')->send();

        if (!$userData) {
            return response()->json(['message' => 'User data not found.'], 404);
        }

        return $this->setCode(code: 200)->setData(new UserDataResource($userData))->setMessage('success')->send();
    }

    public function update(StoreUserDataRequest $request)
    {

        $data = $request->validated();
        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }
        $userId = Auth::id();
        $user = auth()->user();
        $userData = $this->repository->update($user, $data);
        return $this->setCode(code: 200)->setData(($userData))->setMessage('success')->send();

        $userData = $this->repository->getByUserId($userId);
        if (!$userData) {
            // If user data not found, create it
            $data['user_id'] = $userId; // Assuming you need to associate it with the user
            $userData = $this->repository->create($data);
        } else {
            // If found, update it
            $this->repository->update($userId, $data);
            $userData = $this->repository->find($userData->id);
        }
        return $this->setCode(code: 200)->setData(new UserDataResource($userData))->setMessage('success')->send();
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }
}
