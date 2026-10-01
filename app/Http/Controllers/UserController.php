<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DyService;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Spatie\Permission\Models\Role;
use App\Services\NotificationService;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Validation\ValidationException;
use App\Models\Notification as ModelsNotification;

class UserController extends Controller
{

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * get_all_users
     *
     * This function returns all users
     * Parameters: None
     */
    public function get_all_users()
    {
        $users = User::select('username', 'id', 'email', 'status', 'phone')->paginate(50);
        return response(['users' => $users, 'status' => 200]);
    }

    /**
     * get_all_users_without_roles
     *
     * This function returns all users who does not have roles assigned
     * Parameters: None
     */
    public function get_all_users_without_roles()
    {
        $users = User::select('username', 'id')->whereDoesntHave('roles')->get();
        return response(['users' => $users, 'status' => 200]);
    }

    /**
     * get_user
     *
     * This function is to fetch a specific user
     * Parameters: $id - the id of the user
     */
    public function get_user($id)
    {
        $user = User::find($id);
        return response(['user' => $user, 'status' => 200]);
    }


    /**
     * update_user
     *
     * This function is to update a specific user
     * Parameters: $id - the id of the user, $request - the request object for updated data
     */
    public function update_user(Request $request, $id)
    {
        try {
            $fields = $request->validate([
                'username' => 'required|string',
                'city_id' => 'required|integer|exists:cities,id',
                'district_id' => 'required|array',
                'district_id.*' => 'integer|exists:districts,id',
                'email' => 'required|string|email|unique:users,email,' . $id,
                'password' => 'nullable|string|confirmed',
                'status' => 'nullable|string',
                'role' => 'required|string'
            ]);

            $user = User::findOrFail($id);

            if ($user->role == 'technician' && $fields['role'] != 'technician') {
                // Update user and remove following_id
                $fields['following_id'] = null;
            }

            if (!empty($fields['password'])) {
                $fields['password'] = bcrypt($fields['password']);
            }

            $user->update($fields);
            $user->districts()->sync($fields['district_id']);

            $user->load('city', 'districts');

            $responseUser = $user->only(['id', 'username', 'email', 'role']);
            $responseUser['city'] = $user->city;
            $responseUser['district'] = $user->districts;

            return response()->json([
                'employee' => $responseUser,
                'message' => 'Employee updated successfully.',
                'status' => 200
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }



    public function store_customer(Request $request)
    {
        try {
            // Validate the request
            $fields = $request->validate([
                'username' => 'required|string',
                'phone' => 'required|string|unique:users,phone',
                'second_phone' => 'nullable|string',
            ]);

            // Create the user
            $user = User::create([
                'username' => $fields['username'],
                'phone' => $fields['phone'],
                'type' => 'customer',
                'second_phone' => $fields['second_phone'] ?? null,
                'status' => 'active'

            ]);

            $dy = new DyService();

            $response = $dy->StoreCustomer($user->dy_id, $user->username, $user->phone);

            if ($response !== null && $response['ResponseStatus'] === true) {
                $user->update(['dy_integrated' => 1]);
            } else {
                $user->update(['dy_integrated' => 0]);
            }

            // Create a token for the user
            $token = $user->createToken('mypullapp')->plainTextToken;

            $response = [
                'user' => $user,
                'token' => $token,
                'status' => 200
            ];


            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update_customer(Request $request, $id)
    {
        $fields = $request->validate([
            'username' => 'required|string',
            'phone' => 'required|string',
            'second_phone' => 'nullable|string',
        ]);

        $user = User::findOrFail($id);

        if (isset($fields['phone']) && $fields['phone'] !== $user->phone) {
            $request->validate([
                'phone' => 'unique:users,phone'
            ]);
        }

        $user->fill($fields);

        $user->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'phone' => $user->phone,
                'second_phone' => $user->second_phone,
                'status' => $user->status
            ],
            'status' => 200
        ]);
    }

    /**
     * update_user
     *
     * This function is to fetch a specific user
     * Parameters: $id - the id of the user, $request - the request object for updated password
     */
    public function update_user_password(Request $request, $id)
    {
        $fields = $request->validate([
            'password' => 'required|string|confirmed'
        ]);

        $user = User::find($id);

        $user->update([
            'password' => bcrypt($fields['password'])
        ]);

        return response(['user' => $user, 'status' => 200]);
    }

    /**
     * update_user_role
     *
     * This function is to set user to a specific role
     * Parameters: $id - the id of the user, $request - The request object which contain the role name (string)
     */
    public function update_user_role(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->syncRoles([$request->role]);
        return response(['user' => $user, 'status' => 200]);
    }


    /**
     * update_multi_user_roles
     *
     * This function is to set multi users to a specific role
     * Parameters: $id - the id of the role, $request - The request object which contain array of users IDs
     */
    public function update_multi_user_roles(Request $request, $id)
    {
        $role = Role::findById($id);

        foreach ($request->users as $user) {
            $the_user = User::find($user);
            $the_user->assignRole($role->name);
        }

        return response(['role' => $role, 'status' => 200]);
    }

    /**
     * remove_user_role
     *
     * This function is to set user to a specific role
     * Parameters: $id - the id of the user, $request - The request object which contain the role name (string)
     */
    public function remove_user_role($id)
    {
        $user = User::findOrFail($id);
        $user->roles()->detach();
        return response(['user' => $user, 'status' => 200]);
    }


    public function setToken(Request $request)
    {
        $token = $request->input('fcm_token');
        $request->user()->update([
            'fcm_token' => $token
        ]); //Get the currrently logged in user and set their token
        return response()->json([
            'message' => 'Successfully Updated FCM Token'
        ]);
    }


    public function sendNotification(Request $request)
    {
        $user = $request->user('api');

        // Example request fields: 'title', 'body', and 'recipient_token'
        $title = $request->input('title');
        $body = $request->input('body');
        $notification_id = $request->input('notification_id');
        $recipientToken = $request->input('recipient_token');

        $notifiable_id = $request->user('api')->id;

        $result = $this->notificationService->sendNotification($title, $body, $recipientToken, $notifiable_id, $notification_id);

        return response()->json([
            'message' => $result ? 'Notification sent successfully' : 'Notification failed',
        ]);
    }
}
