<?php

namespace App\Http\Resources\Refactor\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Address\AddressResource;
use App\Http\Resources\Refactor\Product\ProductResource;
use App\Http\Resources\Refactor\User\Client\ClientResource;

class OrderResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new ClientResource($this->user),
            'address' => new AddressResource($this->address),
            'currency' => $this->currency,
            'tax' => $this->tax,
            'total_amount' => $this->total_amount,
            'total_price' => $this->total_price,
            'billing_email' => $this->billing_email,
            'payment_method' => $this->payment_method,
            'shipment_note' => $this->shipment_note,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'products' => $this->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->desc,
                    'quantity' => $product->pivot->quantity,
                    'standard' => $product->pivot->standard,
                    'right_standard' => $product->pivot->right_standard,
                    'right_quantity' => $product->pivot->right_quantity,
                    'left_standard' => $product->pivot->left_standard,
                    'left_quantity' => $product->pivot->left_quantity,
                    'price' => $product->price,
                    'images' => $product->images->map(function ($image) {
                        return   $image->image;
                    }),
                ];
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
