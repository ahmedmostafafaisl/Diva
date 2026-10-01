<?php

namespace App\Http\Controllers\Refactor\User;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Services\ValidationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Refactor\Address\AddressResource;
use App\Http\Requests\Refactor\Address\GetAddressRequest;
use App\Http\Requests\Refactor\Address\StoreAddressRequest;
use App\Repositories\Interfaces\AddressRepositoryInterface;
use App\Http\Requests\Refactor\Address\UpdateAddressRequest;


class AddressController extends Controller
{
    use ApiResponseHelper;
    private $addressRepository;
    private $validationService;
    public function __construct(AddressRepositoryInterface $addressRepository, ValidationService $validationService)
    {
        $this->addressRepository = $addressRepository;
        $this->validationService = $validationService;
    }

    public function index()
    {
        $addresses = $this->addressRepository->getAllAddresses();
        return $this->setCode(code: 200)->setData(AddressResource::collection($addresses))->setMessage('success')->send();
    }

    public function store(StoreAddressRequest $request)
    {
        $address = $this->addressRepository->createAddress($request->validated());
        return $this->setCode(code: 200)->setData((new AddressResource($address)))->setMessage('Address created successfully')->send();
    }

    public function show($id)
    {
        $address = $this->validationService->checkRecordExists(Address::class, $id);
        if ($address instanceof \Illuminate\Http\JsonResponse) {
            return $address;
        }
        $address = $this->addressRepository->getAddressById($id);
        return $this->setCode(code: 200)->setData((new AddressResource($address)))->setMessage('success')->send();
    }

    public function update(UpdateAddressRequest $request, $id)
    {

        $address = $this->validationService->checkRecordExists(Address::class, $id);
        if ($address instanceof \Illuminate\Http\JsonResponse) {
            return $address;
        }
        $address = $this->addressRepository->updateAddress($id, $request->validated());
        return $this->setCode(code: 200)->setData((new AddressResource($address)))->setMessage('Address Updated successfully')->send();
    }

    public function destroy($id)
    {
        $address = $this->validationService->checkRecordExists(Address::class, $id);
        if ($address instanceof \Illuminate\Http\JsonResponse) {
            return $address;
        }
        $this->addressRepository->deleteAddress($id);
        return $this->setCode(code: 200)->setData($id)->setMessage('Address deleted successfully')->send();
    }


    public function getAllAddressesForSpecificClient(GetAddressRequest $request)
    {
        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }

        $addresses = $this->addressRepository->getAllAddressesForSpecificClient($request);
        return $this->setCode(code: 200)->setData((($addresses)))->setMessage('success')->send();
    }


    public function getDefaultAddress()
    {

        if (!auth()->check()) {
            return $this->setCode(code: 401)->setData([])->setMessage('Unauthorized')->send();
        }
        return   $address = $this->addressRepository->getDefaultAddress();
        // if ($address->isEmpty()) {
        //     return $this->setCode(code: 404)->setData([])->setMessage('No default address found')->send();
        // }
        return $this->setCode(code: 200)->setData((($address)))->setMessage('success')->send();
    }
}
