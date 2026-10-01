<?php

namespace App\Http\Controllers\Refactor\List;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Refactor\List\WaitingListResource;
use App\Repositories\Interfaces\WaitingListRepositoryInterface;
use App\Http\Requests\Refactor\List\AddOrRemoveProductWaitingRequest;

class WaitingListController extends Controller
{
    private $waitingListRepository;

    public function __construct(WaitingListRepositoryInterface $waitingListRepository)
    {
        $this->waitingListRepository = $waitingListRepository;
    }

    public function index()
    {
        $waitingList = $this->waitingListRepository->getAuthUserWaitingList();

        if (!$waitingList) {
            return response()->json(['message' => 'Waiting list not found'], 404);
        }

        return new WaitingListResource($waitingList);
    }

    public function store(AddOrRemoveProductWaitingRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $result = $this->waitingListRepository->create($data);

        if (is_string($result)) {
            return response()->json(['message' => $result], 400);
        }

        return new WaitingListResource($result);
    }

    public function update(UpdateWaitingListRequest $request, $id)
    {
        $data = $request->validated();

        $result = $this->waitingListRepository->update($id, $data);

        if (is_string($result)) {
            return response()->json(['message' => $result], 404);
        }

        return new WaitingListResource($result);
    }

    public function destroy($id)
    {
        $result = $this->waitingListRepository->delete($id);

        if ($result !== true) {
            return response()->json(['message' => $result], 404);
        }

        return response()->json(null, 204);
    }

    public function addProduct(AddOrRemoveProductWaitingRequest $request)
    {
        $result = $this->waitingListRepository->addProduct($request->product_id);

        if ($result !== true) {
            return response()->json(['message' => $result], 400);
        }

        return response()->json(['message' => 'Product added to waiting list']);
    }

    public function removeProduct(AddOrRemoveProductWaitingRequest $request)
    {
        $result = $this->waitingListRepository->removeProduct($request->product_id);

        if ($result !== true) {
            return response()->json(['message' => $result], 400);
        }

        return response()->json(['message' => 'Product removed from waiting list']);
    }
    public function getUserWaitingList()
    {
        $user = auth()->user();
        $waitingList = $this->waitingListRepository->getByUserId($user->id);

        if (!$waitingList) {
            return response()->json(['message' => 'Waiting list not found'], 404);
        }

        return response()->json(new WaitingListResource($waitingList));
    }
}
