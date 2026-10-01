<?php

namespace App\Http\Controllers\Refactor\Zone;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ShippingInterface;
use App\Http\Requests\Refactor\Zone\ShippingRequest;
use App\Http\Resources\Refactor\Zone\ShippingResource;

class ShippingController extends Controller
{
    protected $shippingRepo;

    public function __construct(ShippingInterface $shippingRepo)
    {
        $this->shippingRepo = $shippingRepo;
    }

    public function index()
    {
        return ShippingResource::collection($this->shippingRepo->all());
    }

    public function store(ShippingRequest $request)
    {
        $shipping = $this->shippingRepo->create($request->validated());
        return new ShippingResource($shipping);
    }

    public function show($id)
    {
        return new ShippingResource($this->shippingRepo->find($id));
    }

    public function update(ShippingRequest $request, $id)
    {
        $shipping = $this->shippingRepo->update($id, $request->validated());
        return new ShippingResource($shipping);
    }

    public function destroy($id)
    {
        $this->shippingRepo->delete($id);
        return response()->json(['message' => 'Shipping deleted successfully']);
    }
}
