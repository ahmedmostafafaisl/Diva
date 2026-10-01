<?php

namespace App\Http\Controllers\Refactor\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Services\ValidationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Refactor\User\UserResource;
use App\Http\Requests\Refactor\User\StoreUserRequest;
use App\Http\Requests\Refactor\User\UpdateUserRequest;
use App\Http\Resources\Refactor\User\EmployeeResource;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Http\Resources\Refactor\User\SingleEmployeeResource;
use App\Http\Requests\Refactor\User\UpdateUserPasswordRequest;
use App\Http\Resources\Refactor\User\UserWithAttachResource;

class UserController extends Controller
{
    use ApiResponseHelper;
    private $validationService;
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository, ValidationService $validationService)
    {
        $this->userRepository = $userRepository;
        $this->validationService = $validationService;
    }

    public function index(): JsonResponse
    {
        $users = $this->userRepository->getAllUsers();
        return $this->setCode(code: 200)->setData(UserResource::collection($users))->setMessage('success')->send();
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userRepository->createUser($request->validated());
        return $this->setCode(code: 200)->setData(new EmployeeResource($user))->setMessage('User Created successfully')->send();
    }

    public function show($id): JsonResponse
    {
        $user = $this->validationService->checkRecordExists(User::class, $id);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        $user = $this->userRepository->getUserById($id);
        return $this->setCode(code: 200)->setData(new SingleEmployeeResource($user))->setMessage('success')->send();
    }

    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $user = $this->validationService->checkRecordExists(User::class, $id);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        $user = $this->userRepository->updateUser($id, $request->validated());
        return $this->setCode(code: 200)->setData(new SingleEmployeeResource($user))->setMessage('User Updated successfully')->send();
    }

    public function updateUserPassword(UpdateUserPasswordRequest $request, $id): JsonResponse
    {
        $user = $this->validationService->checkRecordExists(User::class, $id);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        $user = $this->userRepository->updateUserPassword($id, $request->password);
        return $this->setCode(code: 200)->setData(new UserWithAttachResource($user))->setMessage('Password updated successfully')->send();
    }
    public function destroy($id): JsonResponse
    {
        $user = $this->validationService->checkRecordExists(User::class, $id);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        $this->userRepository->deleteUser($id);
        return $this->setCode(code: 200)->setData($id)->setMessage('User deleted successfully')->send();
    }
}
