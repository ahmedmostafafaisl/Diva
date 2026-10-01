<?php

namespace App\Http\Controllers\Refactor\Wishlist;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Refactor\Wishlist\WishlistRequest;
use App\Http\Resources\Refactor\Wishlist\WishlistResource;
use App\Repositories\Interfaces\WishlistRepositoryInterface;

class WishlistController extends Controller
{

    use ApiResponseHelper;

    protected $wishlistRepository;

    public function __construct(WishlistRepositoryInterface $wishlistRepository)
    {
        $this->wishlistRepository = $wishlistRepository;
    }

    public function getUserWishlist()
    {
        $wishlist = $this->wishlistRepository->getUserWishlist(Auth::id());

        if (!$wishlist) {
            return $this->setCode(code: 404)->setData([])->setMessage('Wishlist not found')->send();
        }
        return $this->setCode(code: 200)->setData(new WishlistResource($wishlist))->setMessage('success')->send();
    }

    public function addToWishlist(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = $this->wishlistRepository->addToWishlist(Auth::id(), $request->product_id);
        return $this->setCode(code: 200)->setData(new WishlistResource($wishlist))->setMessage('Product Added to wishlist successfully')->send();
    }

    public function removeFromWishlist($productId)
    {
        $wishlist = $this->wishlistRepository->removeFromWishlist(Auth::id(), $productId);

        if (!$wishlist) {
            return $this->setCode(code: 404)->setData([])->setMessage('Wishlist not found')->send();
        }
        return $this->setCode(code: 200)->setData([])->setMessage('Product removed from wishlist successfully')->send();
    }


    public function all()
    {
        return Wishlist::with('products')->paginate(10);
    }

    public function find($id)
    {
        return Wishlist::with('products')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Wishlist::create($data);
    }

    public function update($id, array $data)
    {
        $wishlist = $this->find($id);
        $wishlist->update($data);
        return $wishlist;
    }

    public function delete($id)
    {
        $wishlist = $this->find($id);
        return $wishlist->delete();
    }
}
