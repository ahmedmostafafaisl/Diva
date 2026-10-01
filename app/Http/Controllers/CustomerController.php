<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\City;
use App\Models\User;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Address;
use App\Models\CarType;
use App\Models\Package;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\Address2;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\District;
use App\Models\CouponUser;
use App\Models\Appointment;
use App\Services\DyService;
use Illuminate\Support\Arr;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\PushNotifications;
use App\Http\Resources\CouponResource;
use Spatie\Activitylog\Models\Activity;
use Berkayk\OneSignal\OneSignalFacade as OneSignal;

class CustomerController extends Controller
{


    public function get_single_client_historical_activity($id)
    {
        $activities = Activity::select('created_at', 'causer_id', 'properties', 'event')->where('log_name', 'clients' . $id)->orWhere([['log_name', '=', 'Clients'], ['subject_id', '=', $id]])->get();
        foreach ($activities as $activity) {
            $email = User::select('email')->where('id', $activity->causer_id)->first();
            $activity->causer_email = $email->email;
            unset($activity->causer_id);
            // if ($activity->event != 'updated' || $activity->event != 'updated address' || $activity->event != 'create address') {
            //     unset($activity->properties);
            // }
        }
        return response(['activities' => $activities, 'status' => 200]);
    }
    public function get_all_clients()
    {
        $clients = User::where('type', 'customer')->get();
        return response(['clients' => $clients, 'status' => 200]);
    }

    public function search_clients_by_phone_individual(Request $request)
    {
        $client = User::where([['phone', '=', $request->phone], ['type', '=', 'customer']])
            ->orWhere([['second_phone', $request->phone], ['type', '=', 'customer']])
            ->first();

        if (!$client) {
            return response(['message' => 'Client not found'], 404);
        }
        activity('Clients' . $client->id)
            ->event('search')
            ->causedBy(auth()->user())
            ->performedOn($client)
            ->log("Searched client by phone: {$request->phone})");

        return response(['client' => $client, 'status' => 200]);
    }


    public function update_customer_profile(Request $request)
    {
        $id = $request->user()->id;
        $request->validate([
            'username' => 'required|string',
            'phone' => [
                'required',
                Rule::unique('users')->where(function ($query) use ($request, $id) {
                    return $query->where('id', '!=', $id)->where('phone', $request->phone);
                }),
            ],
            'email' => 'required|string|email|unique:users,email,' . $id,
        ]);
        $user = User::findOrFail($id);
        $user->username = $request->username;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->update();

        return response(['message' => 'Updated successfully', 'user' => $user, 'status' => 200]);
    }


    public function storeCustomerWithAddress(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'username' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'city_id' => 'required|integer|exists:cities,id',
            'district_id' => 'required|integer|exists:districts,id',
            'location_note' => 'nullable|string',
            'type' => 'required|string',
        ]);

        // Create the customer
        $customer = User::create([
            'username' => $validatedData['username'],
            'phone' => $validatedData['phone'],
            'type' => 'customer',
        ]);
        $dy = new DyService();

        $response = $dy->StoreCustomer($customer->dy_id, $customer->username, $customer->phone);

        if ($response !== null && $response['ResponseStatus'] === true) {
            $customer->update(['dy_integrated' => 1]);
        } else {
            $customer->update(['dy_integrated' => 0]);
        }
        // Create the address
        $address = Address2::create([
            'user_id' => $customer->id,
            'city_id' => $validatedData['city_id'],
            'district_id' => $validatedData['district_id'],
            'type' => $validatedData['type'],
            'lat' =>  null,
            'long' => null,
            'location_note' => $validatedData['location_note'] ?? '',
        ]);

        // Load the related data
        $address->load('city', 'district');

        // Prepare the response data
        $responseCustomer = $customer->only(['id', 'username', 'phone']);
        $responseCustomer['address'] = [
            'id' => $address->id,
            'city_id' => $address->city_id,
            'city_name' => $address->city->name,
            'district_id' => $address->district_id,
            'district_name' => $address->district->name,
            'address' => $address->address,
            'lat' => $address->lat,
            'long' => $address->long,
            'location_note' => $address->location_note,
        ];

        return response()->json([
            'customer' => $responseCustomer,
            'message' => 'Customer and address saved successfully.',
            'status' => 200,
        ], 200);
    }

    public function getAddressLattAndLong($id)
    {
        $address = Address2::select('lat', 'long')->where('id', $id)->first();
        return response()->json(['address' => $address, 'status' => 200]);
    }
    public function updateAddressLattAndLong(Request $request, $id)
    {
        $request->validate([
            'lat' => 'required|string',
            'long' => 'required|string',
        ]);
        $address = Address2::findOrFail($id);
        $address->lat = $request->lat;
        $address->long = $request->long;
        $address->save();
        return response()->json(['address' => $address, 'status' => 200]);
    }

    public function get_all_client_appointments(Request $request, $id)
    {
        $appointments = Appointment::where('customer_id', $id)->with('products', 'services')->get();
        $appointments = $appointments->map(function ($appointment) {
            $appointment['service'] = Service::select('name_ar')->where('id', $appointment->services[0]->service_id)->first()->name_ar;
            $appointment['technician_name'] = User::select('username')->where('id', $appointment->tech_id)->first()->username;
            $appointment['customer_name'] = User::select('username')->where('id', $appointment->customer_id)->first()->username;
            $appointment['customer_phone'] = User::select('phone')->where('id', $appointment->customer_id)->first()->phone;
            $appointment['city_name'] = City::select('name')->where('id', $appointment->city_id)->first()->name;
            $appointment['district_name'] = District::select('name')->where('id', $appointment->district_id)->first()->name;
            $appointment['dy_invoice_id'] = $appointment->dy_invoice_id ?? 'unknown';
            return $appointment;
        });
        return response(['status' => 200, 'appointments' => $appointments]);
    }
    public function get_all_client_subscriptions(Request $request, $id)
    {

        $subscriptions = Subscription::where('customer_id', $id)->with(['customer', 'package'])->get();

        $response = $subscriptions->map(function ($subscription) {
            // Use Carbon to ensure dates are handled properly
            $startDate = Carbon::parse($subscription->created_at)->format('Y-m-d');
            $endDate = Carbon::parse($subscription->expires_at)->format('Y-m-d');
            $remainingOccurrences = $subscription->remaining_count;
            $current_date = Carbon::now()->format('Y-m-d');

            // Determine package status
            if ($remainingOccurrences == 0 || $current_date > $endDate) {
                $packageStatus = 'expired';
            } else {
                $packageStatus = 'active';
            }

            return [
                'id' => $subscription->id,
                'clientId' => $subscription->customer->id,
                'clientName' => $subscription->customer->username,
                'clientPhone' => $subscription->customer->phone,
                'dy_invoice_id' => $subscription->dy_invoice_id,
                'packageId' => $subscription->package->id,
                'packageName' => $subscription->package->name_ar,
                'packagePrice' => $subscription->package->price,
                'packageDuration' => $subscription->package->duration,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'remainingOccurrences' => $remainingOccurrences,
                'packageStatus' => $packageStatus,
            ];
        });
        return response(['status' => 200, 'subscriptions' => $response]);
    }

    public function get_all_client_coupons(Request $request, $id)
    {
        $coupons = CouponUser::where('user_id', $id)
            ->where('is_used', 1)
            ->with('coupon')
            ->get()
            ->map(function ($couponUser) {
                return $couponUser->coupon;
            })
            ->unique('id'); // Ensure uniqueness by coupon ID
        return response(['coupons' => CouponResource::collection($coupons), 'status' => 200]);
    }



    public function add_new_address(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'city_id' => 'required|integer',
            'district_id' => 'required|integer',
            'type' => 'nullable|string',
            'name' => 'nullable|string',
            'lat' => 'nullable|string',
            'long' => 'nullable|string',
            'location_note' => 'nullable|string',
        ]);

        $client_address = new Address();
        $client_address->user_id = $request->user_id;
        $client_address->city_id = $request->city_id;
        $client_address->district_id = $request->district_id;
        $client_address->type = $request->type;
        $client_address->name = $request->name ? $request->name : null;
        $client_address->lat = $request->lat ? $request->lat : null;
        $client_address->long = $request->long ? $request->long : null;
        $client_address->location_note = $request->location_note  ? $request->location_note  : null;
        $client_address->save();


        if ($client_address->district_id) {
            $old_district = District::select('name')->where('id', $client_address->district_id)->first();
            $client_address->district_name = $old_district->name;
        }

        if ($client_address->city_id) {
            $new_city = City::select('name')->where('id', $client_address->city_id)->first();
            $client_address->city_name = $new_city->name;
        }



        return response(['address' => $client_address, 'status' => 200]);
    }

    public function update_address(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'city_id' => 'required|integer',
            'district_id' => 'required|integer',
            'type' => 'nullable|string',
            'name' => 'nullable|string',
            'lat' => 'nullable|string',
            'long' => 'nullable|string',
            'location_note' => 'nullable|string',
        ]);

        $client_address = Address::findOrFail($id);
        $client_address->client_id = $request->client_id;
        $client_address->city_id = $request->city_id;
        $client_address->district_id = $request->district_id;
        $client_address->type = $request->type;
        $client_address->name = $request->name ? $request->name : null;
        $client_address->lat = $request->lat;
        $client_address->long = $request->long;
        $client_address->location_note = $request->location_note  ? $request->location_note  : null;
        $client_address->save();
        return response(['address' => $client_address, 'status' => 200]);
    }


    public function allAdressesForSpecficClients($id)
    {
        $addresses = Address::where('user_id', $id)
            ->where('status', 'active')
            ->with(['city:id,name', 'district:id,name'])
            ->get();


        if ($addresses->isEmpty()) {
            return response()->json(['message' => 'No addresses found for the given user ID.', 'status' => 404]);
        }

        $addresses = $addresses->map(function ($address) {
            return [
                'id' => $address->id,
                'type' => $address->type,
                'name' => $address->name,
                'city_name' => $address->city ? $address->city->name : null,
                'district_name' => $address->district ? $address->district->name : null,
                'location_note' => $address->location_note,
            ];
        });

        return response()->json(['addresses' => $addresses, 'status' => 200]);
    }



    public function delete_address($id)
    {
        $address = Address::findOrFail($id);
        $address->status = 'inactive';
        $address->update();
        return response(['address' => $address, 'message' => 'Address deleted successfully', 'status' => 200]);
    }


    public function get_all_B2B_clients()
    {
        $clients = Client::where('type', 'B2B')->get();
        return response(['clients' => $clients, 'status' => 200]);
    }

    public function get_single_client($id)
    {
        $client = Client::with(['addresses.city', 'addresses.district'])->find($id);

        if (!$client) {
            return response(['message' => 'Client not found'], 404);
        }

        activity('Clients')
            ->event('view')
            ->causedBy(auth()->user())
            ->performedOn($client)
            ->log("Fetched client: {$client->full_name} (ID: {$client->id})");

        return response(['client' => $client, 'status' => 200]);
    }


    public function search_clients_by_phone_company(Request $request)
    {
        $client = Client::where([['phone', '=', $request->phone], ['type', '=', 'B2B']])
            ->orWhere([['second_phone', $request->phone], ['type', '=', 'B2B']])
            ->first();

        activity('Clients' . $client->id)
            ->event('search')
            ->causedBy(auth()->user())
            ->performedOn($client)
            ->log("Searched client by phone: {$request->phone})");

        return response(['client' => $client, 'status' => 200]);
    }

    public function add_new_client(Request $request)
    {

        $request->validate([
            'full_name' => 'required|string',
            'type' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id',
            'phone' => [
                'required',
                Rule::unique('clients')->where(function ($query) use ($request) {
                    return $query->where('phone', $request->phone)
                        ->orWhere('second_phone', $request->phone);
                }),
            ],
            'second_phone' => [
                'nullable',
                Rule::unique('clients')->where(function ($query) use ($request) {
                    return $query->where('second_phone', $request->second_phone)
                        ->orWhere('phone', $request->second_phone);
                }),
            ],
            'address' => 'required|array',
            'address.*.city_id' => [
                'required',
                'exists:cities,id',
                function ($attribute, $value, $fail) {
                    if (!City::where('id', $value)->where('status', 'Active')->exists()) {
                        $fail('The selected city is not active.');
                    }
                },
            ],
            'address.*.district_id' => 'required|exists:districts,id',
            'address.*.location_note' => 'nullable|string',
            'location_note' => 'nullable|string',
        ]);


        $user = auth()->user();

        $client =  Client::create([
            'full_name' => $request->full_name,
            'type' => $request->type,
            'phone' => $request->phone,
            'second_phone' => $request->second_phone ?? null,
            'location_note' => $request->location_note ?? null,
            'whatsapp_notifications' => 1,
            'city_id' => $request->city_id,
            'district_id' => $request->district_id,
            'user_id' => $user->id,


        ]);

        if ($request->address && count($request->address) > 0) {
            foreach ($request->address as $address) {
                $client_address = new Address();
                $client_address->default_address = 1;
                $client_address->client_id = $client->id;
                $client_address->city_id = $address['city_id'];
                $client_address->district_id = $address['district_id'];
                $client_address->location_note = $address['location_note'] ?? null;
                $client_address->save();
            }
        }

        return response(['clients' => $client, 'status' => 200]);
    }



    public function get_all_active_appointments(Request $request)
    {

        $id = $request->user()->id;
        $appointments = Appointment::select('id', 'package_id', 'dy_invoice_id', 'total_price', 'appointment_num', 'address_id', 'payment_type', 'status', 'car_count', 'appointment_date', 'start_time', 'car_type_id', 'second_car_type_id', 'city_id', 'district_id')
            ->where('customer_id', $id)
            ->where(function ($query) {
                $query->where('status', 'ongoing')
                    ->orWhere('status', 'upcoming')
                    ->orWhere('status', 'departured')
                    ->orWhere('status', 'reached')
                    ->orWhere('status', 'started')
                    ->orWhere('status', 'rescheduled_by_tech')
                    ->orWhere('status', 'rescheduled_by_customer')
                    ->orWhere('status', 'rescheduled_by_cs');
            })
            ->with('services', 'address')
            ->get();
        $appointments = $appointments->map(function ($appointment) {
            if ($appointment->package_id !== null) {
                $appointment['service'] = Package::select('name_ar')->where('id', $appointment->package_id)->first()->name_ar;
                $appointment['service_id'] = $appointment->package_id;
            } else {
                $appointment['service'] = Service::select('name_ar')->where('id', $appointment->services[0]->service_id)->first()->name_ar;
                $appointment['service_id'] = $appointment->services[0]->service_id;
            }
            $appointment['id'] = $appointment->id;
            $appointment['city'] = City::select('name')->where('id', $appointment->city_id)->first()->name;
            $appointment['district'] = District::select('name')->where('id', $appointment->district_id)->first()->name;
            $appointment['address_type'] = $appointment->address->type;
            $appointment['address_name'] = $appointment->address->name;
            $appointment['reschedule_count'] = $appointment->reschedule_count;
            $appointment['total_price'] = $appointment->total_price;
            $appointment['remaining_days'] = now()->diffInDays($appointment->appointment_date);
            $appointment['dy_invoice_id'] = $appointment->dy_invoice_id;
            unset($appointment->services);
            return $appointment;
        });

        return response(['appointments' => $appointments, 'status' => 200]);
    }

    public function get_all_inactive_appointments(Request $request)
    {
        $id = $request->user()->id;
        $appointments = Appointment::where('customer_id', $id)
            ->where(function ($query) {
                $query->where('status', 'cancelled_by_cs')
                    ->orWhere('status', 'completed')
                    ->orWhere('status', 'cancelled_by_tech')
                    ->orWhere('status', 'cancelled_by_customer');
            })
            ->select('id', 'package_id', 'total_price', 'dy_invoice_id', 'appointment_num', 'address_id', 'payment_type', 'status', 'appointment_date', 'start_time', 'vehicle_id', 'city_id', 'district_id')
            ->with('services', 'address')
            ->get();
        $appointments = $appointments->map(function ($appointment) {
            if ($appointment->package_id !== null) {
                $appointment['service'] = Package::select('name_ar')->where('id', $appointment->package_id)->first()->name_ar;
                $appointment['service_id'] = $appointment->package_id;
            } else {
                $appointment['service'] = Service::select('name_ar')->where('id', $appointment->services[0]->service_id)->first()->name_ar;
                $appointment['service_id'] = $appointment->services[0]->service_id;
            }
            $appointment['id'] = $appointment->id;
            $appointment['city'] = City::select('name')->where('id', $appointment->city_id)->first()->name;
            $appointment['district'] = District::select('name')->where('id', $appointment->district_id)->first()->name;
            $appointment['address_type'] = $appointment->address->type;
            $appointment['address_name'] = $appointment->address->name;

            $appointment['reschedule_count'] = $appointment->reschedule_count;
            $appointment['total_price'] = $appointment->total_price;
            $appointment['dy_invoice_id'] = $appointment->dy_invoice_id;
            unset($appointment->services);
            return $appointment;
        });
        return response(['appointments' => $appointments, 'status' => 200]);
    }

    // UPDATE USER NOTIFICATION ID
    public function update_user_notification_id(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|string',
        ]);
        $id = $request->user()->id;
        $user = User::findOrFail($id);
        $user->notification_id = $request->notification_id;
        $user->update();

        return response(['message' => 'Updated successfully', 'user' => $user, 'status' => 200]);
    }

    public function test()
    {
        $message = new PushNotifications();
        $message->sendNotificationToUser('test', '85753ff6-d2aa-4e5a-a212-7b9e75c46911');
    }

    public function sendNotification()
    {
        $message = 'message';
        $title = 'title';
        $userId = 'd751131b-cfd4-4997-9c1e-6227a1999e47';
        try {
            OneSignal::sendNotificationToUser(
                $message,
                $userId,
                null,
                null,
                null,
                $title,
            );

            return response()->json(['success' => true, 'message' => 'Notification sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function get_all_client_cars(Request $request, $id)
    {
        $vehicles = Vehicle::where('status', 'active')->where('customer_id', $id)->get();
        foreach ($vehicles as $vehicle) {
            $vehicle['brand_name'] = CarBrand::where('id', $vehicle['car_brand_id'])->first()->name;
            $vehicle['brand_image'] = CarBrand::where('id', $vehicle['car_brand_id'])->first()->image;
            $vehicle['model_name'] = CarModel::where('id', $vehicle['car_model_id'])->first()->name;
        }
        return response()->json(['vehicles' => $vehicles,  'status' => 200]);
    }
}
