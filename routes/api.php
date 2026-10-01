<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Refactor\Payment\new\TabbyNewPaymentController;
use App\Http\Controllers\Refactor\Payment\new\TamaraNewPaymentController;
use App\Http\Controllers\TabbyPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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



Route::get('/testenv', function () {

    if (env('APP_ENV') == 'local') {
        return response()->json(['url' => env('DY_TEST_BASE_URL')]);
    } elseif (env('APP_ENV') == 'staging') {
        return response()->json(['url' => env('DY_TEST_BASE_URL')]);
    } elseif (env('APP_ENV') == 'production') {
        return response()->json(['url' => env('DY_LIVE_BASE_URL')]);
    }
});



//////// tamara ///////////////////

Route::get('/payment/pre_checkout', [PaymentController::class, 'pre_checkout']);


Route::post('/payment/create', [PaymentController::class, 'createOrderFromApp'])->name('payment.create');

Route::get('/payment/handle', [PaymentController::class, 'handle']);



Route::post('/payment/authorize', [PaymentController::class, 'authorizeOrder'])->name('payment.authorize');

Route::post('/payment/capture', [PaymentController::class, 'captureOrder'])->name('payment.capture');


// Route::get('/payment/success', function () {
//     return 'Payment successful!';
// })->name('payment.success');

// Route::get('/payment/failure', function () {
//     return 'Payment failed!';
// })->name('payment.failure');

// Route::get('/payment/cancel', function () {
//     return 'Payment canceled!';
// })->name('payment.cancel');

Route::get('/payment/notification', [PaymentController::class, 'handleNotification'])->name('payment.notification');


Route::get('/payment/checkMerchantStatus', [PaymentController::class, 'checkMerchantStatus'])->name('payment.checkMerchantStatus');


//// Zon

////// feature/appointment_or_subscription_without_invoice_id ///////////////////
Route::get('/app/subs/non_invoice', action: [PaymentController::class, 'non_invoice']);


Route::get('/status', function () {
    return response()->json(['status' => 'ok'], 200);
});




Route::get('/tabby/checkout', [TabbyPaymentController::class, 'createCheckout'])->name('tabby.checkout');
//

Route::post('tabby/session/status', [PaymentController::class, 'getPaymentStatus']);




Route::match(['get', 'post'], '/WhatsApp/receive', function (Request $request) {
    Log::info('📩 WhatsApp POST payload', $request->all());

    // ✅ Step 1: Handle GET Verification
    if ($request->isMethod('get')) {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === '00IZCYL5Ab1vafxxKfc6ihAMxTz4cVQH') {
            Log::info('✅ WhatsApp webhook verified successfully.');
            return response($challenge, 200);
        }

        Log::error('❌ Invalid verify token');
        return response('Verification token mismatch', 403);
    }

    // ✅ Step 2: Handle POST Notification
    $data = $request->all();
    Log::info('📩 WhatsApp POST payload', $data);

    $entry = $data['entry'][0]['changes'][0]['value'] ?? null;

    if (!$entry) {
        Log::warning('⚠️ No entry data found in webhook payload.');
        return response()->json(['status' => 'ok']);
    }

    // 💬 Handle incoming message
    if (isset($entry['messages'][0])) {
        $message = $entry['messages'][0];
        Log::info('💬 Incoming message detected', $message);

        // Get phone number and message text or button payload
        $from = $message['from'] ?? null;
        $type = $message['type'] ?? null;

        if ($type === 'text') {
            $text = $message['text']['body'] ?? '';
        } elseif ($type === 'button') {
            $text = $message['button']['payload'] ?? $message['button']['text'] ?? '';
        } else {
            $text = '[Unsupported message type]';
        }

        // Log the basic info
        Log::info('📞 WhatsApp message from: ' . $from);
        Log::info('📝 Message content: ' . $text);

        // ✅ Try to extract structured data (like your payload format)
        preg_match_all('/(\w+):([^\n]+)/u', $text, $matches, PREG_SET_ORDER);

        $parsed = [];
        foreach ($matches as $m) {
            $key = trim($m[1]);
            $value = trim($m[2]);
            $parsed[$key] = $value;
        }

        Log::info('📋 Parsed WhatsApp payload', $parsed);


    }

    return response()->json(['status' => 'ok']);
});



//new
Route::get('/tamara/success/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newSuccess'])->name('new.tamara.success');
Route::post('/tamara/notification/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newNotification'])->name('new.tamara.notification');
Route::get('/tamara/cancel/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newCancel'])->name('new.tamara.cancel');
Route::get('/tamara/webhook/reference_id={reference_id}', [TamaraNewPaymentController::class, 'newWebhook'])->name('new.tamara.failure');

Route::get('/tabby/success', [TabbyNewPaymentController::class, 'success'])->name('new.tabby.success');
Route::get('/tabby/cancel', [TabbyNewPaymentController::class, 'cancel'])->name('new.tabby.cancel');
Route::get('/tabby/failure', [TabbyNewPaymentController::class, 'failure'])->name('new.tabby.failure');
