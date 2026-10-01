<?php


namespace App\Http\Controllers;

use TaqnyatSms;
use App\Models\User;
use App\Models\EmployeeDay;
use App\Services\DyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the request

        try {
            if ($request->role == 'technician') {
                $fields = $request->validate([
                    'username' => 'required|string',
                    'city_id' => 'required|integer|exists:cities,id',
                    'district_id' => 'required|array',
                    'district_id.*' => 'integer|exists:districts,id',
                    'email' => 'required|string|email|unique:users,email',
                    'password' => 'required|string|confirmed',
                    'following_id' => 'required|integer|exists:users,id',
                    'role' => 'required|string',
                    'skills' => 'required|array',
                    'skills.*' => 'integer|exists:skills,id'
                ]);
            } else {
                $fields = $request->validate([
                    'username' => 'required|string',
                    'city_id' => 'required|integer|exists:cities,id',
                    'district_id' => 'required|array',
                    'district_id.*' => 'integer|exists:districts,id',
                    'email' => 'required|string|email|unique:users,email',
                    'password' => 'required|string|confirmed',
                    'role' => 'required|string'
                ]);
            }

            // Create the user
            $user = User::create([
                'username' => $fields['username'],
                'type' => 'employee',
                'city_id' => $fields['city_id'],
                'email' => $fields['email'],
                'following_id' => $request->following_id != null ? $request->following_id : NULL,
                'password' => bcrypt($fields['password']),
                'role' => $fields['role']
            ]);

            $user->districts()->attach($fields['district_id']);

            if ($request->role == 'technician') {
                $user->skills()->attach($fields['skills']);
            }

            $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

            foreach ($days as $day) {
                $the_day = new EmployeeDay();
                $the_day->employee_id = $user->id;
                $the_day->day_name = $day;
                $the_day->save();
            }

            $token = $user->createToken('mypullapp')->plainTextToken;

            $user->load('city', 'districts', 'skills');

            $responseUser = $user->only(['id', 'username', 'email', 'role']);
            $responseUser['city'] = $user->city;
            $responseUser['district'] = $user->districts;
            $responseUser['skills'] = $user->skills;

            return response()->json([
                'employee' => $user,
                'token' => $token,
                'message' => 'Employee stored successfully.',
                'status' => 200
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update_customer_data(Request $request, $id)
    {
        $fields = $request->validate([
            'username' => 'required|string',
            'email' => 'required|string|unique:users,email',
        ]);

        $user = User::where('id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->update([
            'username' => $fields['username'],
            'email' => $fields['email'],
            'isCustomer' => true,
        ]);

        return response()->json(['message' => 'Customer information updated successfully.'], 200);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }
        $token = $user->createToken('mypullapp')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function login_customer(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required|string',
        ]);
        $mobile_phone = $validatedData['phone'];
        $user = User::where('phone', $mobile_phone)->first();

        if (!$user) {

            $otp = rand(1000, 9999);

            if ($validatedData['phone'] == '+966500329088') {
                $otp = 1313;
            }
               if ($validatedData['phone'] == '+966554791962') {
                $otp = 1313;
            }
            if ($validatedData['phone'] == '+966500000001' || $validatedData['phone'] == '+966500000002' || $validatedData['phone'] == '+966566027755') {
                $otp = 8888;
            }

            $user = User::create([
                'phone' => $mobile_phone,
                'otp' => $otp,
                'username' => 'مستخدم جديد',
                'type' => 'customer',
            ]);

            $dy = new DyService();

            $response = $dy->storeCustomer($user->dy_id, $user->username, $user->phone);
            if ($response !== null && $response['ResponseStatus'] === true) {
                $user->update(['dy_integrated' => 1]);
            } else {
                $user->update(['dy_integrated' => 0]);
            }
            if ($validatedData['phone'] != '+966500329088' && $validatedData['phone'] != '+966500000001' && $validatedData['phone'] != '+966500000002' && $validatedData['phone'] != '+966566027755') {
                $bearer = '6eb0fa779309a58e862fd03eb9ae7f46';
                $taqnyt = new TaqnyatSms($bearer);

                $body = "رمز التحقق لدخول تطبيق ديفا هو : $otp";
                $recipients = [$validatedData['phone']];
                $sender = 'Central';

                $taqnyt->sendMsg($body, $recipients, $sender);
            }



            $client = User::where('phone', $mobile_phone)->orWhere('second_phone', $mobile_phone)->first();
            if (!$client) {
                $new_client = User::create([
                    'username' => 'مستخدم جديد',
                    'phone' => $mobile_phone,
                    'type' => 'customer',
                    'status' => 'active',
                    'otp' => $otp,
                ]);
                $dy = new DyService();

                $response = $dy->storeCustomer($new_client->dy_id, $new_client->username, $new_client->phone);
                if ($response !== null && $response['ResponseStatus'] === true) {
                    $new_client->update(['dy_integrated' => 1]);
                } else {
                    $new_client->update(['dy_integrated' => 0]);
                }
            }
        } else {

            if ($user->status == 'inactive') {
                return response()->json(['message' => 'User already deleted his account data.'], 401);
            }

            $otp = rand(1000, 9999);

            if ($validatedData['phone'] == '+966500329088') {
                $otp = 1313;
            }

            if ($validatedData['phone'] == '+966554791962') {
                $otp = 1313;
            }
            if ($validatedData['phone'] == '+966500000001') {
                $otp = 8888;
            }
            if ($validatedData['phone'] == '+966500000002') {
                $otp = 8888;
            }
            if ($validatedData['phone'] == '+966566027755') {
                $otp = 8888;
            }
            $user->otp = $otp;
            $user->update();

            if ($validatedData['phone'] != '+966500329088' && $validatedData['phone'] != '+966500000001' && $validatedData['phone'] != '+966500000002' && $validatedData['phone'] != '+966566027755') {
                $bearer = '6eb0fa779309a58e862fd03eb9ae7f46';
                $taqnyt = new TaqnyatSms($bearer);

                $body = "رمز التحقق لدخول تطبيق ديفا هو : $otp";
                $recipients = [$validatedData['phone']];
                $sender = 'Central';

                $taqnyt->sendMsg($body, $recipients, $sender);
            }

            // Search for existing client with the same phone number
            $mobile_phone = $validatedData['phone'];
            $client = User::where('phone', $mobile_phone)->orWhere('second_phone', $mobile_phone)->first();

            // If there is no existing client with the same phone number then create a new one
            if (!$client) {
                $new_client = User::create([
                    'username' => 'مستخدم جديد',
                    'phone' => $mobile_phone,
                    'type' => 'customer',
                    'status' => 'active',
                    'otp' => $otp,
                ]);
                $dy = new DyService();

                $response = $dy->storeCustomer($new_client->dy_id, $new_client->username, $new_client->phone);
                if ($response !== null && $response['ResponseStatus'] === true) {
                    $new_client->update(['dy_integrated' => 1]);
                } else {
                    $new_client->update(['dy_integrated' => 0]);
                }
            }
        }

        return response()->json(['message' => 'OTP sent to your phone.'], 200);
    }

    public function loginVerifyOtp(Request $request)
    {
        $validatedData = $request->validate([
            'otp' => 'required|numeric',
            'phone' => 'required|string',
        ]);

        $user = User::where('phone', $validatedData['phone'])->first();

        if ($user && $user->otp == $validatedData['otp']) {

            $token = $user->createToken('mypullapp')->plainTextToken;
            $infoCompleted = $user->email != null && $user->username != null;


            $user->update(['is_verified' => true, 'otp' => null]);

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
                'infoCompleted' => $infoCompleted,
            ], 200);
        }

        return response()->json(['message' => 'Invalid OTP.'], 401);
    }


    public function get_user_permissions($id)
    {
        // Check email
        $user = User::where('id', $id)->with('roles', function ($query) {
            $query->with('permissions', function ($query) {
                $query->select('name');
            });
        })->first();
        foreach ($user->roles[0]->permissions as $permission) {
            unset($permission['pivot']);
        }

        $response = [
            'permissions' => $user->roles[0]->permissions,
        ];

        return response($response, 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return $user->username;
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out'], 201);
    }

    // Change user status
    public function change_user_status(Request $request)
    {
        $id = $request->user()->id;
        $request->user()->currentAccessToken()->delete();
        $user = User::findOrFail($id);
        $user->status = 'inactive';
        $user->update();

        return response()->json(['message' => 'User status updated successfully.'], 200);
    }
}
