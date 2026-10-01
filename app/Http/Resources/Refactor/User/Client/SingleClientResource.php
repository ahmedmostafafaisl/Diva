<?php

namespace App\Http\Resources\Refactor\User\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Appointment\AppointmentResource;
use App\Http\Resources\Refactor\Subscription\SubscriptionResource;

class SingleClientResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'customer-id' => $this->id,
            'type' => $this->type,
            'username' => $this->username,
            'phone' => $this->phone,
            'appointments-number' => $this->clientAppointments()->count(),
            'subscription-number' => $this->subscriptions()->count(),
            'vehicles-number' => $this->vehicles()->count(),
            'coupons-number' => $this->coupons()->count(),
            'created-at' => $this->created_at,
            'updated-at' => $this->updated_at,
            'addresses' => $this->addresses ? $this->addresses->map(function ($address) {
                return [
                    'location-id' => $address->id,
                    'type' => $address->type,
                    'city_id' => $address->city->id ?? null,
                    'city_name' => $address->city->name ?? null,
                    'district_id' => $address->district->id ?? null,
                    'district_name' => $address->district->name ?? null,
                    'location_note' => $address->location_note,
                    'status' => $address->status,

                ];
            }) : [],
            'coupons' => $this->coupons ? $this->coupons->map(function ($coupon) {
                return [
                    'coupon-id' => $coupon->id,
                    'name' => $coupon->name,
                    'status' => $coupon->status,
                    'reference_id' => $coupon->reference_id,
                    'type' => $coupon->type,
                    'usage_count' => $coupon->usage_count,
                    'start_date' => $coupon->start_date,
                    'expiry_date' => $coupon->expiry_date,
                    'usage_limit_per_user' => $coupon->usage_limit_per_user,
                    'discount_type' => $coupon->discount_type,
                    'discount_amount' => $coupon->discount_amount,
                    'first_time_only' => $coupon->first_time_only,
                    'created_at' => $coupon->created_at,
                    'updated_at' => $coupon->updated_at,
                ];
            }) : [],
            'vehicles' => $this->vehicles ? $this->vehicles->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'customer_id' => $vehicle->customer_id,
                    'car_brand_id' => $vehicle->car_brand_id,
                    'car_brand_name' => $vehicle->brand->name ?? null,
                    'car_model_id' => $vehicle->car_model_id,
                    'car_model_name' => $vehicle->model->name ?? null,
                    'plate_text' => $vehicle->plate_text,
                    'plate_number' => $vehicle->plate_number,
                    'color' => $vehicle->color,
                    'status' => $vehicle->status,
                    'created_at' => $vehicle->created_at,
                    'updated_at' => $vehicle->updated_at,
                ];
            }) : [],
            'appointments' => AppointmentResource::collection($this->customerAppointments) ?? [],
            'subscriptions' => (SubscriptionResource::collection($this->subscriptions)) ?? [],
        ];
    }
}
