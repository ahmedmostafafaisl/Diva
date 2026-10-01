<?php

namespace App\Http\Controllers\Refactor\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Services\ValidationService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Http\Resources\Refactor\User\Client\ClientResource;
use App\Http\Requests\Refactor\User\Client\CreateClientRequest;
use App\Http\Requests\Refactor\User\Client\SearchClientRequest;
use App\Http\Requests\Refactor\User\Client\UpdateClientRequest;
use App\Http\Requests\Refactor\User\Client\UpdateFcmTokenRequest;
use App\Http\Resources\Refactor\User\Client\SingleClientResource;
use App\Http\Requests\Refactor\User\Client\CreateClientWithAddressRequest;

class ClientController extends Controller
{
    private $clientRepository;
    use ApiResponseHelper;
    private $validationService;
    public function __construct(ClientRepositoryInterface $clientRepository, ValidationService $validationService)
    {
        $this->clientRepository = $clientRepository;
        $this->validationService = $validationService;
    }

    public function index(Request $request)
    {
        if ($request->type == "client") {
            $users = DB::table('users')->where('type', 'customer')->select('id', 'username', 'phone')->get();
            return $this->setCode(code: 200)->setData($users)->setMessage('success')->send();
        }

        // return   $users = User::where('type', 'customer')->select('id', 'phone')->get();

        $perPage = $request->input('per_page', 10);
        $page = $request->input('current_page', 1);
        $clients = User::where('type', 'customer')->get();
        $numberOfPages =  ceil((($clients->count()) / $perPage));
        return $this->setCode(code: 200)->setData(['clients' => ClientResource::collection($this->clientRepository->all($perPage, $page)->items()), "Page Number" => $page, "Number Of Pages" => $numberOfPages])->setMessage('success')->send();
    }

    public function store(CreateClientRequest $request)
    {
        $client = $this->clientRepository->create($request->validated());
        return $this->setCode(code: 200)->setData(new ClientResource($client))->setMessage('success')->send();
    }

    public function show($id)
    {
        $client = $this->validationService->checkRecordExists(User::class, $id);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        $client = $this->clientRepository->find($id);
        return $this->setCode(code: 200)->setData(new SingleClientResource($client))->setMessage('success')->send();
    }

    public function update(UpdateClientRequest $request, $id)
    {
        $client = $this->validationService->checkRecordExists(User::class, $id);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        $client = $this->clientRepository->update($id, $request->validated());
        return $this->setCode(code: 200)->setData(new ClientResource($client))->setMessage('success')->send();
    }
    ///

    public function searchClientByPhone(SearchClientRequest $request)
    {
        $client = $this->clientRepository->searchClientByPhone($request->phone);

        if (!$client) {
            return response(['message' => 'Client not found'], 404);
        }
        return $this->setCode(code: 200)->setData(new ClientResource($client))->setMessage('success')->send();
    }

    public function searchClient(SearchClientRequest $request)
    {
        $client = $this->clientRepository->searchClient($request);

        if (!$client) {
            return response(['message' => 'Client not found'], 404);
        }
        return $this->setCode(code: 200)->setData(ClientResource::collection($client))->setMessage('success')->send();
    }

    public function updateCustomerProfile(Request $request)
    {
        $data = $request->all();
        $data['id'] = $request->user()->id;
        return $this->setCode(code: 200)->setData($this->clientRepository->updateCustomerProfile($data))->setMessage('success')->send();
    }

    public function storeCustomerWithAddress(CreateClientWithAddressRequest $request)
    {
        $data = $request->all();
        $result = $this->clientRepository->storeCustomerWithAddress($data);
        return $this->setCode(code: 200)->setData($result)->setMessage('success')->send();
    }
    public function getAllClientAppointments(int $id)
    {
        $client = $this->validationService->checkRecordExists(User::class, $id);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        $appointments = $this->clientRepository->getAllClientAppointments($id);
        return $this->setCode(code: 200)->setData($appointments)->setMessage('success')->send();
    }

    public function getAllClientSubscriptions(int $id)
    {
        $client = $this->validationService->checkRecordExists(User::class, $id);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        $subscriptions = $this->clientRepository->getAllClientSubscriptions($id);
        return $this->setCode(code: 200)->setData($subscriptions)->setMessage('success')->send();
    }

    public function getAllClientCoupons(int $id)
    {
        $client = $this->validationService->checkRecordExists(User::class, $id);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        $coupons = $this->clientRepository->getAllClientCoupons($id);
        return $this->setCode(code: 200)->setData($coupons)->setMessage('success')->send();
    }

    public function getClientAddresses(int $id)
    {
        $client = $this->validationService->checkRecordExists(User::class, $id);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        $addresses = $this->clientRepository->getClientAddresses($id);
        return $this->setCode(code: 200)->setData($addresses)->setMessage('success')->send();
    }

    public function updateToken(UpdateFcmTokenRequest $request)
    {
        $user = $request->user();
        if (is_null($user)) {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated')->send();
        }
        $token = $request->input('fcm_token');
        $user->update([
            'fcm_token' => $token
        ]);
        $user->save();
        return $this->setCode(code: 200)->setData($user)->setMessage('success')->send();
    }
}
