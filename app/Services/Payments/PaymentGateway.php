<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use App\Models\Address2;
use Illuminate\Support\Collection;

class PaymentGateway
{
    public function __construct(
        private TabbyGateway $tabby,
        private TamaraGateway $tamara,
    ) {}

    public function initCheckout(string $method, Order $order, User $user, Address2 $address, Collection $cartProducts): array
    {
        return match ($method) {
            'tabby'  => $this->tabby->checkout($order, $user, $address, $cartProducts),
            'tamara' => $this->tamara->checkout($order, $user, $address, $cartProducts),
            default  => [
                'payment_id' => null,
                'payment_url' => null,
                'provider' => 'apple_pay',
                'extra' => [],
            ],
        };
    }
}
