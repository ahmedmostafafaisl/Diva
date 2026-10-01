<?php

namespace App\Repositories\Cart;

use App\Helper\ApiResponseHelper;
use App\Http\Resources\Refactor\Cart\CartResource;
use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Product;
use App\Models\Variation;
use App\Models\VariationAddon;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Services\FirebaseNotificationService;
use Illuminate\Validation\ValidationException;

class CartRepository implements CartRepositoryInterface
{
    use ApiResponseHelper;

    protected $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function getUserCart($userId)
    {
        $cart = Cart::where('user_id', $userId)
            ->with([
                'products.product.images',
                'products.variation',
            ])
            ->first();

        if (! $cart) {
            return $this->setCode(404)
                ->setData([])
                ->setMessage('cart not found')
                ->send();
        }

        $taxRate = 0.15;

        $taxableGross = 0.0;
        $nonTaxableTotal = 0.0;

        foreach ($cart->products ?? [] as $cartProduct) {
            $p = $cartProduct->product;
            if (! $p) {
                continue;
            }

            $lineGross = (float) ($cartProduct->line_total ?? 0);
            $isTaxable = (bool) ($p->tax ?? false);

            if ($isTaxable) {
                $taxableGross += $lineGross;
            } else {
                $nonTaxableTotal += $lineGross;
            }
        }

        $totalPrice = $taxableGross + $nonTaxableTotal;

        if ($taxableGross > 0) {
            $taxableNet = $taxableGross / (1 + $taxRate);
            $taxAmount = $taxableGross - $taxableNet;
            $priceBeforeTax = $taxableNet + $nonTaxableTotal;
        } else {
            $taxAmount = 0.0;
            $priceBeforeTax = $totalPrice;
        }

        return $this->setCode(200)
            ->setData([
                'cart' => new CartResource($cart),
                'total_price' => number_format($totalPrice, 2, '.', ''),
                'price_before_tax' => number_format($priceBeforeTax, 2, '.', ''),
                'tax' => number_format($taxAmount, 2, '.', ''),
            ])
            ->setMessage('success')
            ->send();
    }

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
    ) {
        $cart = Cart::firstOrCreate(['user_id' => $userId]);

        $product = Product::findOrFail($productId);

        $variation = null;
        $unitPrice = (float) ($product->price ?? $product->sale_price ?? $product->regular_price ?? 0);
        $rightPrice = null;
        $leftPrice = null;
        $lineTotal = 0.0;

        if ($variationId) {
            $variation = Variation::where('id', $variationId)
                ->where('product_id', $productId)
                ->with('addons')
                ->first();

            if (! $variation) {
                throw ValidationException::withMessages([
                    'variation_id' => ['Selected variation is invalid for this product.'],
                ]);
            }

            if ($this->isLensMedicalVariation($variation)) {
                if ($rightStandard !== null && $rightQuantity !== null) {
                    $this->assertVisionPowerExists($variation->id, 'right', (string) $rightStandard);

                    $rightPrice = $this->resolveLensSidePrice(
                        $variation->id,
                        'right',
                        (string) $rightQuantity,
                        $unitPrice
                    );
                }

                if ($leftStandard !== null && $leftQuantity !== null) {
                    $this->assertVisionPowerExists($variation->id, 'left', (string) $leftStandard);

                    $leftPrice = $this->resolveLensSidePrice(
                        $variation->id,
                        'left',
                        (string) $leftQuantity,
                        $unitPrice
                    );
                }

                $lineTotal = (float) ($rightPrice ?? 0) + (float) ($leftPrice ?? 0);
            } else {
                $qty = max((int) ($quantity ?? 1), 1);
                $lineTotal = $unitPrice * $qty;
            }
        } else {
            $qty = max((int) ($quantity ?? 1), 1);
            $lineTotal = $unitPrice * $qty;
        }

        $cartProduct = CartProduct::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('variation_id', $variationId)
            ->where('standard', $standard)
            ->where('right_standard', $rightStandard)
            ->where('right_quantity', $rightQuantity)
            ->where('left_standard', $leftStandard)
            ->where('left_quantity', $leftQuantity)
            ->first();

        if ($cartProduct) {
            $cartProduct->update([
                'quantity' => $quantity,
                'standard' => $standard,
                'right_standard' => $rightStandard,
                'right_quantity' => $rightQuantity,
                'right_price' => $rightPrice,
                'left_standard' => $leftStandard,
                'left_quantity' => $leftQuantity,
                'left_price' => $leftPrice,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        } else {
            $cartProduct = CartProduct::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $quantity,
                'standard' => $standard,
                'right_standard' => $rightStandard,
                'right_quantity' => $rightQuantity,
                'right_price' => $rightPrice,
                'left_standard' => $leftStandard,
                'left_quantity' => $leftQuantity,
                'left_price' => $leftPrice,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }

        if (
            ($cartProduct->quantity !== null && $cartProduct->quantity <= 0) &&
            ($cartProduct->right_quantity === null || $cartProduct->right_quantity <= 0) &&
            ($cartProduct->left_quantity === null || $cartProduct->left_quantity <= 0)
        ) {
            $cartProduct->delete();
        }

        return $cart->load([
            'products.product.images',
            'products.variation',
        ]);
    }

    public function removeFromCart(int $userId, int $cartProductId)
    {
        $cart = Cart::where('user_id', $userId)->first();

        if (! $cart) {
            return null;
        }

        CartProduct::where('cart_id', $cart->id)
            ->where('id', $cartProductId)
            ->delete();

        return $cart->load([
            'products.product.images',
            'products.variation',
        ]);
    }

    public function updateCartQuantity(
        int $userId,
        int $cartProductId,
        ?int $quantity = null,
        ?int $rightQuantity = null,
        ?int $leftQuantity = null
    ) {
        $cart = Cart::firstOrCreate(['user_id' => $userId]);

        $cartProduct = CartProduct::where('cart_id', $cart->id)
            ->where('id', $cartProductId)
            ->with(['product', 'variation'])
            ->first();

        if (! $cartProduct) {
            throw ValidationException::withMessages([
                'cart_product_id' => ['Cart product not found.'],
            ]);
        }

        $cartProduct->quantity = $quantity ?? $cartProduct->quantity;
        $cartProduct->right_quantity = $rightQuantity ?? $cartProduct->right_quantity;
        $cartProduct->left_quantity = $leftQuantity ?? $cartProduct->left_quantity;

        if ($cartProduct->variation_id) {
            $variation = Variation::with('addons')->find($cartProduct->variation_id);

            if ($variation && $this->isLensMedicalVariation($variation)) {
                $basePrice = (float) ($cartProduct->unit_price ?? 0);

                if ($cartProduct->right_quantity && $cartProduct->right_standard !== null) {
                    $cartProduct->right_price = $this->resolveLensSidePrice(
                        $variation->id,
                        'right',
                        (string) $cartProduct->right_quantity,
                        $basePrice
                    );
                } else {
                    $cartProduct->right_price = null;
                }

                if ($cartProduct->left_quantity && $cartProduct->left_standard !== null) {
                    $cartProduct->left_price = $this->resolveLensSidePrice(
                        $variation->id,
                        'left',
                        (string) $cartProduct->left_quantity,
                        $basePrice
                    );
                } else {
                    $cartProduct->left_price = null;
                }

                $cartProduct->line_total =
                    (float) ($cartProduct->right_price ?? 0) +
                    (float) ($cartProduct->left_price ?? 0);
            } else {
                $basePrice = (float) ($cartProduct->unit_price ?? 0);
                $qty = max((int) ($cartProduct->quantity ?? 1), 1);
                $cartProduct->line_total = $basePrice * $qty;
            }
        } else {
            $basePrice = (float) ($cartProduct->unit_price ?? 0);
            $qty = max((int) ($cartProduct->quantity ?? 1), 1);
            $cartProduct->line_total = $basePrice * $qty;
        }

        $cartProduct->save();

        if (
            ($cartProduct->quantity !== null && $cartProduct->quantity <= 0) &&
            ($cartProduct->right_quantity === null || $cartProduct->right_quantity <= 0) &&
            ($cartProduct->left_quantity === null || $cartProduct->left_quantity <= 0)
        ) {
            $cartProduct->delete();
        }

        return $cart->load([
            'products.product.images',
            'products.variation',
        ]);
    }

    private function resolveLensSidePrice(
        int $variationId,
        string $side,
        string $qtyLabel,
        float $fallbackBasePrice
    ): float {
        $addon = VariationAddon::where('variation_id', $variationId)
            ->where('addon_type', 'quantity_price')
            ->where('side', $side)
            ->where('label', (string) ((int) $qtyLabel))
            ->first();

        if (! $addon) {
            throw ValidationException::withMessages([
                $side.'_quantity' => ['Selected quantity is invalid for this variation.'],
            ]);
        }

        $addonPrice = (float) ($addon->price ?? 0);

        if ($addonPrice <= 0) {
            return $fallbackBasePrice;
        }

        return $addonPrice;
    }

    private function assertVisionPowerExists(int $variationId, string $side, string $powerLabel): void
    {
        $normalizedInput = $this->normalizePowerLabel($powerLabel);

        $options = VariationAddon::where('variation_id', $variationId)
            ->where('addon_type', 'vision_power')
            ->where('side', $side)
            ->pluck('label');

        $exists = $options->contains(function ($label) use ($normalizedInput) {
            return $this->normalizePowerLabel((string) $label) === $normalizedInput;
        });

        if (! $exists) {
            throw ValidationException::withMessages([
                $side.'_standard' => ['Selected vision power is invalid for this variation.'],
            ]);
        }
    }

    private function normalizePowerLabel(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        $number = (float) $value;

        if ($number > 0) {
            return '+'.rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
        }

        if ($number < 0) {
            return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
        }

        return '0';
    }

    private function isLensMedicalVariation(Variation $variation): bool
    {
        $type = mb_strtolower(trim((string) ($variation->type ?? '')));

        return in_array($type, ['نظر', 'medical', 'medical lenses'], true)
            || $variation->addons()->where('addon_type', 'vision_power')->exists()
            || $variation->addons()->where('addon_type', 'quantity_price')->exists();
    }
}
