<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', function () {
    return view('welcome');
})->name('home');



Route::get('/test', function () {
    return 'test';
});

Route::get('/linkstorage', function () {
    $targetFolder = base_path() . '/storage/app/public';
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . '/storage';
    symlink($targetFolder, $linkFolder);
    return 'done';
});

Route::get('/clear', function () {

    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    return "Cleared!";
});


Route::get('/custom', function (Request $request) {
    return response()->json([
        'data' => $request->all()
    ]);
});


Route::post('/WhatsApp/receive', function (Request $request) {
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

    if (
        isset($data['entry'][0]['changes'][0]['value']['messages'][0])
    ) {
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from']; // sender phone number
        $text = $message['text']['body'] ?? null;
        $type = $message['type'];

        Log::info("💬 New WhatsApp message from {$from}: {$text}");

        // Example: Save message to DB or trigger reply
        // Message::create([...]);
    } else {
        Log::info('ℹ️ No message found in payload (maybe status update)');
    }

    return response()->json(['status' => 'ok']);
});
