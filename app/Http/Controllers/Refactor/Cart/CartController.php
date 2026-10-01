<?php

namespace App\Http\Controllers\Refactor\Cart;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Refactor\Cart\CartResource;
use App\Repositories\Interfaces\CartRepositoryInterface;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponseHelper;

    protected $cartRepository;

    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function getUserCart()
    {
        return $this->cartRepository->getUserCart(auth()->id());
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|exists:variations,id',

            'quantity' => 'nullable|integer|min:1',
            'standard' => 'nullable|numeric',

            'right_standard' => 'nullable|numeric',
            'right_quantity' => 'nullable|integer|min:1',

            'left_standard' => 'nullable|numeric',
            'left_quantity' => 'nullable|integer|min:1',
        ]);

        $cart = $this->cartRepository->addToCart(
            auth()->id(),
            (int) $request->product_id,
            $request->variation_id ? (int) $request->variation_id : null,
            $request->quantity ? (int) $request->quantity : null,
            $request->standard,
            $request->right_standard,
            $request->right_quantity ? (int) $request->right_quantity : null,
            $request->left_standard,
            $request->left_quantity ? (int) $request->left_quantity : null
        );

        return $this->setCode(200)
            ->setData(new CartResource($cart))
            ->setMessage('Product added to cart successfully')
            ->send();
    }

    public function addToCart2(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variation_id' => 'nullable|exists:variations,id',

            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.standard' => 'nullable|numeric',

            'products.*.right_standard' => 'nullable|numeric',
            'products.*.right_quantity' => 'nullable|integer|min:1',

            'products.*.left_standard' => 'nullable|numeric',
            'products.*.left_quantity' => 'nullable|integer|min:1',
        ]);

        $cart = null;

        foreach ($request->products as $productData) {
            $cart = $this->cartRepository->addToCart(
                auth()->id(),
                (int) $productData['product_id'],
                isset($productData['variation_id']) ? (int) $productData['variation_id'] : null,
                isset($productData['quantity']) ? (int) $productData['quantity'] : null,
                $productData['standard'] ?? null,
                $productData['right_standard'] ?? null,
                isset($productData['right_quantity']) ? (int) $productData['right_quantity'] : null,
                $productData['left_standard'] ?? null,
                isset($productData['left_quantity']) ? (int) $productData['left_quantity'] : null
            );
        }

        return $this->setCode(200)
            ->setData(new CartResource($cart))
            ->setMessage('Product(s) added to cart successfully')
            ->send();
    }

    public function removeFromCart(Request $request)
    {
        $request->validate([
            'cart_product_id' => 'required|exists:cart_products,id',
        ]);

        $cart = $this->cartRepository->removeFromCart(
            auth()->id(),
            (int) $request->cart_product_id
        );

        return $this->setCode(200)
            ->setData(new CartResource($cart))
            ->setMessage('Product removed from cart successfully')
            ->send();
    }

    public function updateCartQuantity(Request $request)
    {
        $request->validate([
            'cart_product_id' => 'required|exists:cart_products,id',
            'quantity' => 'nullable|integer|min:1',
            'right_quantity' => 'nullable|integer|min:1',
            'left_quantity' => 'nullable|integer|min:1',
        ]);

        $cart = $this->cartRepository->updateCartQuantity(
            auth()->id(),
            (int) $request->cart_product_id,
            $request->quantity ? (int) $request->quantity : null,
            $request->right_quantity ? (int) $request->right_quantity : null,
            $request->left_quantity ? (int) $request->left_quantity : null
        );

        return $this->setCode(200)
            ->setData(new CartResource($cart))
            ->setMessage('Cart quantity updated successfully')
            ->send();
    }
}
