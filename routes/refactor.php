<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Refactor\Banner\BannerController;
use App\Http\Controllers\Refactor\Cart\CartController;
use App\Http\Controllers\Refactor\CenterialController;
use App\Http\Controllers\Refactor\Chat\ChatController;
use App\Http\Controllers\Refactor\Gate\GateController;
use App\Http\Controllers\Refactor\List\ListController;
use App\Http\Controllers\Refactor\List\WaitingListController;
use App\Http\Controllers\Refactor\Notification\NotificationController;
use App\Http\Controllers\Refactor\Order\OrderController;
use App\Http\Controllers\Refactor\Payment\new\TabbyNewPaymentController;
use App\Http\Controllers\Refactor\Payment\new\TamaraNewPaymentController;
use App\Http\Controllers\Refactor\Payment\TamaraPaymentController;
use App\Http\Controllers\Refactor\Product\ProductController;
use App\Http\Controllers\Refactor\Requests\FollowRequestController;
use App\Http\Controllers\Refactor\Review\ReviewController;
use App\Http\Controllers\Refactor\SubCategoryController;
use App\Http\Controllers\Refactor\Sug\GateSuggestionController;
use App\Http\Controllers\Refactor\Sug\SubCategorySuggestionController;
use App\Http\Controllers\Refactor\User\AddressController;
use App\Http\Controllers\Refactor\User\ClientController;
use App\Http\Controllers\Refactor\User\UserAuthController;
use App\Http\Controllers\Refactor\User\UserController;
use App\Http\Controllers\Refactor\User\UserDataController;
use App\Http\Controllers\Refactor\User\UserFriendController;
use App\Http\Controllers\Refactor\User\UserProductListController;
use App\Http\Controllers\Refactor\Wishlist\WishlistController;
use App\Http\Controllers\Refactor\Zone\ShippingController;
use App\Http\Controllers\Refactor\Zone\ZoneController;
use App\Http\Controllers\TabbyPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('ref')->group(function () {

    Route::get('/search-customer/{phone}', [CenterialController::class, 'searchCustomerByPhone']);

    // users
    Route::apiResource('users', UserController::class);
    // update User Password
    Route::put('users/{id}/password', [UserController::class, 'updateUserPassword']);

    // addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::get('/{id}', [AddressController::class, 'show']);
        Route::put('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::post('/client/addresses', [AddressController::class, 'getAllAddressesForSpecificClient']);
        Route::get('/default/address', [AddressController::class, 'getDefaultAddress']);
    });

    // Banners
    Route::prefix('banners')->group(function () {
        Route::get('/', [BannerController::class, 'index']);
        Route::get('/{id}', [BannerController::class, 'show']);
        Route::post('/', [BannerController::class, 'store']);
        Route::put('/{id}', [BannerController::class, 'update']);
        Route::delete('/{id}', [BannerController::class, 'destroy']);
        Route::get('/active/banners', [BannerController::class, 'getAllActiveBanners']);
    });

    // user Auth
    Route::group(['prefix' => 'auth'], function () {
        // Register Routes
        Route::post('/register', [UserAuthController::class, 'register']);
        // Login Routes
        Route::post('/login', [UserAuthController::class, 'login']);
        // Update Customer Data
        Route::put('/update-customer', [UserAuthController::class, 'updateCustomerData'])->middleware('auth:sanctum');
        // get profile
        Route::get('/get/profile', [UserAuthController::class, 'getCustomerProfile'])->middleware('auth:sanctum');
        // Customer Login Routes
        Route::post('/login-customer', [UserAuthController::class, 'loginCustomer']);
        Route::post('/login/email/customer', [UserAuthController::class, 'loginCustomerEmail']);

        // Customer Login with otp
        Route::post('/verify-otp', [UserAuthController::class, 'loginVerifyOtp']);
        // Logout Routes
        Route::post('/logout', [UserAuthController::class, 'logout']);
        // Permissions Route
        Route::get('/permissions/{id}', [UserAuthController::class, 'getUserPermissions']);
        // Change Auth User Status
        Route::post('/change-status', [UserAuthController::class, 'changeUserStatus']);
        // update online status
        Route::post('/update/online-status', [UserAuthController::class, 'updateOnlineStatus'])->middleware('auth:sanctum');
    });

    // clients
    Route::apiResource('clients', ClientController::class);
    Route::prefix('clients')->group(function () {
        // clients/search
        Route::post('/search', [ClientController::class, 'searchClientByPhone']);
        Route::post('/all/search', [ClientController::class, 'searchClient']);

        // clients/profile
        Route::post('/profile', [ClientController::class, 'updateProfile']);
        // clients/storeWithAddress
        Route::post('/store', [ClientController::class, 'storeCustomerWithAddress']);
        // clients/address/get
        Route::get('/{id}/addresses/all', [ClientController::class, 'getClientAddresses']);
        // clients/appointments
        Route::get('/{id}/appointments', [ClientController::class, 'getAllClientAppointments']);
        // clients/subscriptions
        Route::get('/{id}/subscriptions', [ClientController::class, 'getAllClientSubscriptions']);
        // clients/appointments
        Route::get('/{id}/coupons', [ClientController::class, 'getAllClientCoupons']);
        // update fcm Token
        Route::post('update/fcm-Token', [ClientController::class, 'updateToken']);
    });

    // Gates
    Route::apiResource('gates', GateController::class);
    // SubCategory
    Route::apiResource('subcategories', SubCategoryController::class);
    // Product
    Route::apiResource('products', ProductController::class);
    Route::post('products/search', [ProductController::class, 'searchProducts2']);
    // get subcategory products
    Route::get('sub/{id}/products', [ProductController::class, 'getSubCategoryProducts']);
    // GateSuggestion
    // Route::apiResource('gate-suggestions', GateSuggestionController::class);

    // GateSuggestion
    Route::apiResource('suggestions/sub', SubCategorySuggestionController::class);

    // Wishlist
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('wishlists', WishlistController::class);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/wishlist', [WishlistController::class, 'getUserWishlist']);
        Route::post('/wishlist/add', [WishlistController::class, 'addToWishlist']);
        Route::delete('/wishlist/remove/{productId}', [WishlistController::class, 'removeFromWishlist']);
    });

    // Cart

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/cart', [CartController::class, 'getUserCart']);
        Route::post('/cart/add', [CartController::class, 'addToCart']);
        Route::post('/cart/2/add', [CartController::class, 'addToCart2']);
        Route::delete('/cart/remove', [CartController::class, 'removeFromCart']);
        Route::post('/cart/update-quantity', [CartController::class, 'updateCartQuantity']);
    });

    // Order
    Route::apiResource('orders', OrderController::class);

    Route::get('/user/orders', [OrderController::class, 'getUserOrders']);
    // refund
    Route::get('/user/order/refund', [OrderController::class, 'refundMultipleProducts']);
    // Reviews
    Route::apiResource('reviews', ReviewController::class);
    // Notifications
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications', [NotificationController::class, 'store']);
        Route::get('/notifications/{id}', [NotificationController::class, 'show']);
        Route::put('/notifications/{id}', [NotificationController::class, 'update']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::get('user/notifications', [NotificationController::class, 'getUserNotifications']);
        Route::post('send-push-notification', [NotificationController::class, 'sendPushNotification']);
        Route::get('read/notifications/{id}', [NotificationController::class, 'makeAsRead']);
    });

    Route::get('/products-by-category', [CenterialController::class, 'getProductsByCategory']);
    // Zone
    Route::apiResource('zones', ZoneController::class);
    // Shippings
    Route::apiResource('shippings', ShippingController::class);
    // user - data
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/user-data', [UserDataController::class, 'store']);
        Route::put('/user-data', [UserDataController::class, 'update']);
        Route::get('/user-data', [UserDataController::class, 'show']); // if you want to get for auth user
        Route::delete('/user-data/{id}', [UserDataController::class, 'destroy']);
    });

    // tabby
    Route::get('/tabby/checkout', [TabbyPaymentController::class, 'createCheckout'])->name('tabby.checkout');
    // Route::get('/tabby/success', [TabbyPaymentController::class, 'success'])->name('tabby.success');
    // Route::get('/tabby/cancel', [TabbyPaymentController::class, 'cancel'])->name('tabby.cancel');
    // Route::get('/tabby/failure', [TabbyPaymentController::class, 'failure'])->name('tabby.failure');

    Route::post('tabby/session/status', [PaymentController::class, 'getPaymentStatus']);

    // Beauty Show

    // Get All influencers
    Route::get('/all/influencers', [CenterialController::class, 'allInfluencers']);

    // Get All Posts
    Route::get('/all/posts', [CenterialController::class, 'getAllPosts']);

    // Get Single Post
    Route::get('/post/{id}', [CenterialController::class, 'getSinglePost']);

    // Get All Comments
    Route::get('/post/{id}/comments', [CenterialController::class, 'getAllComments']);

    // Get influencer Followers  And Following
    Route::get('/influencer/{id}/follows', [CenterialController::class, 'getInfluencerFollowersAndFollowing']);

    // Get All Stores
    Route::get('/all/stores', [CenterialController::class, 'getAllStores']);

    // Get All Contents
    Route::get('/user/{id}/contents', [CenterialController::class, 'userContents']);

    // Get All user Products
    Route::get('/user/{id}/products', [CenterialController::class, 'userProducts']);
    // Get All brands
    Route::get('/all/brands', [ProductController::class, 'getUniqueBrands']);
    Route::get('/product/by-brand', [ProductController::class, 'getProductsByBrand']);
    // like Post
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/like/post/{id}', [CenterialController::class, 'likePost']);
        Route::post('/save/post/{id}', [CenterialController::class, 'savePost']);
        Route::post('/save/comment/{id}', [CenterialController::class, 'saveComment']);
        Route::post('/nested/comment/{id}', [CenterialController::class, 'nestedComment']);
        Route::post('/follow/user/{id}', [CenterialController::class, 'followUser']);

        Route::get('/liked/contents', [CenterialController::class, 'likedContents']);
        Route::get('/saved/contents', [CenterialController::class, 'savedContents']);
        Route::get('/Followed/influencers', [CenterialController::class, 'followedInfluencersPosts']);
        Route::get('/collections', [CenterialController::class, 'getAllCollections']);
    });

    // lists
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/lists', [ListController::class, 'index']);
        Route::post('/lists', [ListController::class, 'store']);
        Route::put('/lists/{id}', [ListController::class, 'update']);
        Route::delete('/lists/{id}', [ListController::class, 'destroy']);

        Route::get('/my-lists', [ListController::class, 'userLists']);
        Route::post('/lists/add-product', [ListController::class, 'addProduct']);
        Route::post('/lists/remove-product', [ListController::class, 'removeProduct']);
    });

    // waiting list
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/waiting-list', [WaitingListController::class, 'getUserWaitingList']);
        Route::post('/waiting-list/products', [WaitingListController::class, 'addProduct']);
        Route::delete('/waiting-list/products', [WaitingListController::class, 'removeProduct']);
        // avatar
        Route::post('user/update/avatar', [CenterialController::class, 'updateProfilePicture']);
        Route::post('user/delete/avatar', [CenterialController::class, 'deleteProfilePicture']);
    });

    //  Influencer
    Route::prefix('influencer')->middleware('auth:sanctum')->group(function () {
        Route::get('/posts', [CenterialController::class, 'getInfluencerPosts']);
        Route::post('/create/post', [CenterialController::class, 'createPost']);
        Route::post('/delete/post/{id}', [CenterialController::class, 'deletePost']);
        Route::post('/create/banner', [CenterialController::class, 'createBanner']);
        Route::post('/delete/banner', [CenterialController::class, 'deleteBanner']);
        Route::get('/products', [CenterialController::class, 'getInfluencerProducts']);
        Route::get('/profile', [CenterialController::class, 'getInfluencerProfile']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user-lists', [UserProductListController::class, 'index']);
        Route::get('/user-lists/{subcategoryId}', [UserProductListController::class, 'show']);
        Route::post('/user-lists/add', [UserProductListController::class, 'store']);
        Route::delete('/user-lists/{subcategoryId}/remove/{productId}', [UserProductListController::class, 'destroy']);
        // Route::post('/user-lists/create-defaults', [UserProductListController::class, 'createDefaultLists']);
    });

    // Follow Requests
    Route::middleware('auth:sanctum')->prefix('follow')->group(function () {
        Route::post('/request/{user}', [FollowRequestController::class, 'sendRequest']);
        Route::post('/respond/{user}', [FollowRequestController::class, 'respond']); // Accept/Reject
        Route::get('/my-requests', [FollowRequestController::class, 'myFollowRequests']);
    });

    // user Friends
    Route::middleware('auth:sanctum')->prefix('friends')->group(function () {
        Route::get('/', [UserFriendController::class, 'index']);
        Route::get('/following', [UserFriendController::class, 'following']);
        Route::post('/', [UserFriendController::class, 'store']);
        Route::delete('/{friendId}', [UserFriendController::class, 'destroy']);
    });

    // Chat
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/chat/send', [ChatController::class, 'sendMessage']);
        Route::get('/chat/room/{roomId}', [ChatController::class, 'getMessages']);
        Route::post('/chat/room/create', [ChatController::class, 'createRoom']);
        Route::get('/chat/with/{userId}', [ChatController::class, 'getPrivateMessagesWithUser']);
        // create group chat
        Route::post('/chat/room/create', [ChatController::class, 'createRoomWithUsers']);
        // get groups
        Route::get('/chat/group-rooms', [ChatController::class, 'getMyGroupChatRooms']);
        // send message to a group
        Route::post('/chat/group/send', [ChatController::class, 'sendMessageToGroup']);
        // update group
        Route::put('chat-rooms/{id}/update', [ChatController::class, 'updateGroupRoom']);
        Route::post('chat-rooms/{id}/add-users', [ChatController::class, 'addUsersToRoom']);
        Route::post('chat-rooms/{id}/remove-users', [ChatController::class, 'removeUsersFromRoom']);

        //    // user stories
        Route::get('user/stories', [CenterialController::class, 'getUserStories']);
        Route::post('/user/stories', [CenterialController::class, 'createUserStory']);
        Route::delete('/user/stories/{storyId}', [CenterialController::class, 'deleteUserStory']);
    });
});

// Tamara Payment Routes
// return routes for new tamara integration
Route::post('ref/tamara/checkout', [TamaraPaymentController::class, 'createCheckout'])->name('tamara.checkout');
Route::get('/tamara/success/reference_id={reference_id}', [TamaraPaymentController::class, 'newSuccess'])->name('new.tamara.success');
Route::get('/tamara/cancel/reference_id={reference_id}', [TamaraPaymentController::class, 'newCancel'])->name('new.tamara.cancel');
Route::get('/tamara/webhook/reference_id={reference_id}', [TamaraPaymentController::class, 'newWebhook'])->name('new.tamara.failure');
Route::post('/tamara/notification/reference_id={reference_id}', [TamaraPaymentController::class, 'newNotification'])->name('new.tamara.notification');

// new show route for subcategory with products
Route::get('/ref/subcategories/{id}/products', [SubCategoryController::class, 'newShow']);
// new show single product with subcategory
// Route::get('/ref/products/{id}/sync', [ProductController::class, 'showWithWooSync']);
Route::get('/ref/products/{id}/sync', [ProductController::class, 'showWithWooSync'])
    ->whereNumber('id');

// new

Route::get('/tamara/success/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newSuccess'])->name('new.tamara.success');
Route::get('/tamara/cancel/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newCancel'])->name('new.tamara.cancel');
Route::get('/tamara/webhook/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newWebhook'])->name('new.tamara.failure');
Route::post('/tamara/notification/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newNotification'])->name('new.tamara.notification');

Route::get('/tabby/success', [TabbyNewPaymentController::class, 'success'])->name('new.tabby.success');
Route::get('/tabby/cancel', [TabbyNewPaymentController::class, 'cancel'])->name('new.tabby.cancel');
Route::get('/tabby/failure', [TabbyNewPaymentController::class, 'failure'])->name('new.tabby.failure');
