<?php

namespace App\Repositories\Interfaces;

interface CartRepositoryInterface
{
    public function getUserCart($userId);

    public function addToCart(
        int $userId,
        int $productId,
        ?int $variationId = null,
        ?int $quantity = null,
        $standard = null,
        $rightStandard = null,
        ?int $rightQuantity = null,
        $leftStandard = null,
        ?int $leftQuantity = null
    );

    public function removeFromCart(int $userId, int $cartProductId);

    public function updateCartQuantity(
        int $userId,
        int $cartProductId,
        ?int $quantity = null,
        ?int $rightQuantity = null,
        ?int $leftQuantity = null
    );
}
