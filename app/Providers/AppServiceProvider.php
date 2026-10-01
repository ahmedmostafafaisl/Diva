<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Gate\GateRepository;
use App\Repositories\List\ListRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Zone\ZoneRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\User\ClientRepository;
use App\Repositories\User\AddressRepository;
use App\Repositories\Banner\BannerRepository;
use App\Repositories\Review\ReviewRepository;
use App\Repositories\User\UserAuthRepository;
use App\Repositories\User\UserDataRepository;
use App\Repositories\Zone\ShippingRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\User\UserFriendRepository;
use App\Repositories\List\WaitingListRepository;
use App\Repositories\Wishlist\WishlistRepository;
use App\Repositories\Interfaces\ShippingInterface;
use App\Repositories\User\UserProductListRepository;
use App\Repositories\GateSug\GateSuggestionRepository;
use App\Repositories\Requests\FollowRequestRepository;
use App\Repositories\SubCategory\SubCategoryRepository;
use App\Repositories\Interfaces\CartRepositoryInterface;
use App\Repositories\Interfaces\GateRepositoryInterface;
use App\Repositories\Interfaces\GateSuggestionInterface;
use App\Repositories\Interfaces\ListRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\ZoneRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Interfaces\BannerRepositoryInterface;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\ReviewRepositoryInterface;
use App\Repositories\Interfaces\AddressRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\UserAuthRepositoryInterface;
use App\Repositories\Interfaces\UserDataRepositoryInterface;
use App\Repositories\Interfaces\WishlistRepositoryInterface;
use App\Repositories\GateSug\SubCategorySuggestionRepository;
use App\Repositories\Interfaces\UserFriendRepositoryInterface;
use App\Repositories\Interfaces\SubCategoryRepositoryInterface;
use App\Repositories\Interfaces\WaitingListRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\FollowRequestRepositoryInterface;
use App\Repositories\Interfaces\UserProductListRepositoryInterface;
use App\Repositories\Interfaces\SubCategorySuggestionRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserAuthRepositoryInterface::class, UserAuthRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
        $this->app->bind(BannerRepositoryInterface::class, BannerRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(GateRepositoryInterface::class, GateRepository::class);
        $this->app->bind(SubCategoryRepositoryInterface::class, SubCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(GateSuggestionInterface::class, GateSuggestionRepository::class);
        $this->app->bind(WishlistRepositoryInterface::class, WishlistRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(SubCategorySuggestionRepositoryInterface::class, SubCategorySuggestionRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(ZoneRepositoryInterface::class, ZoneRepository::class);
        $this->app->bind(ShippingInterface::class, ShippingRepository::class);
        $this->app->bind(UserDataRepositoryInterface::class, UserDataRepository::class);
        $this->app->bind(ListRepositoryInterface::class, ListRepository::class);
        $this->app->bind(WaitingListRepositoryInterface::class, WaitingListRepository::class);
        $this->app->bind(UserProductListRepositoryInterface::class, UserProductListRepository::class);
        $this->app->bind(FollowRequestRepositoryInterface::class, FollowRequestRepository::class);
        $this->app->bind(UserFriendRepositoryInterface::class, UserFriendRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
