<?php

namespace App\Http\Controllers\Refactor\Order;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\Refactor\Order\StoreOrderRequest;
use App\Http\Resources\Refactor\Order\OrderResource;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\WooOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use ApiResponseHelper;


    protected $orderRepository;
    protected $wooService;
    public function __construct(OrderRepositoryInterface $orderRepository, WooOrderService $wooService)
    {
        $this->wooService = $wooService;
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request)
    {
        return $this->setCode(code: 200)->setData(OrderResource::collection($this->orderRepository->all($request->status)))->setMessage('success')->send();
    }

    public function store(CreateOrderRequest $request)
    {
        return   $this->orderRepository->store($request->validated());
    }

    public function show($id)
    {
        return $this->setCode(code: 200)->setData(new OrderResource($this->orderRepository->find($id)))->setMessage('success')->send();
    }

    public function update(StoreOrderRequest $request, $id)
    {
        return $this->setCode(code: 200)->setData(new OrderResource($this->orderRepository->update($id, $request->validated())))->setMessage('success')->send();
    }

    public function destroy($id)
    {
        return $this->setCode(code: 200)->setData([])->setMessage('Order deleted successfully')->send();
    }

    public function getUserOrders(Request $request)
    {
        return  $orders = $this->orderRepository->getAuthUserOrders($request->status);
    }

    public function refundMultipleProducts(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'line_items' => 'required|array|min:1',
            'line_items.*.id' => 'required|integer', // update table if different
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order_id = $request->input('order_id');
        $reason = $request->input('reason');
        $line_items = $request->input('line_items');

        $amount = collect($line_items)->sum(function ($item) {
            return $item['quantity'] * $item['price'];
        });
        $amount = number_format($amount, 2, '.', '');
        // dd($amount, $reason, $line_items);
        return  $orderData = $this->wooService->refundMultipleProducts($order_id,  $line_items, $amount, $reason);
    }
}
