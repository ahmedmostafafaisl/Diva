<?php

namespace App\Http\Resources\Refactor\User;

use App\Http\Resources\Refactor\Appointment\CalendarDashAppointmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CalenderEmployeeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'status' => $this->status,
            'following_id' => $this->following_id,
            'following_name' => $this->leader->username ?? null,
            'role' => $this->role,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'appointments' => CalendarDashAppointmentResource::collection($this->appointments),
            // 'appointments' => $this->appointments->map(function ($appointment) {
            //     return [
            //         'id' => $appointment->id,
            //         'tech_id' => $appointment->tech_id,
            //         'appointment_num' => $appointment->appointment_num,
            //         'appointment_date' => $appointment->appointment_date,
            //         'start_time' => $appointment->start_time,
            //         'end_time' => $appointment->end_time,
            //         'payment_id' => $appointment->payment_id,
            //         'dy_sales_id' => $appointment->dy_sales_id,
            //         'dy_invoice_id' => $appointment->dy_invoice_id,
            //         'q_invoice_id' => $appointment->q_invoice_id,
            //         'car_brand_id' => $appointment->car_brand_id,
            //         'car_model_id' => $appointment->car_model_id,
            //         'car_type_id' => $appointment->car_type_id,
            //         'service_ar' => $this->getFirstService_ar() ?? null,
            //         'service_en' => $this->getFirstService_en() ?? null,


            //         'subscription_id' => $appointment->subscription_id,
            //         'customer_id' => $appointment->customer_id,
            //         'appointment_duration' => $appointment->appointment_duration,
            //         'reschedule_count' => $appointment->reschedule_count,
            //         'total_price' => $appointment->total_price,
            //         'tax' => $appointment->tax,
            //         'payment_image' => $this->getImageUrl($appointment->payment_image),
            //         'subtotal' => $appointment->subtotal,
            //         'discount' => $appointment->discount,
            //         'invoice_id' => $appointment->q_invoice_id,
            //         'cash_in_hand' => $appointment->cash_in_hand,
            //         'payment_type' => $appointment->payment_type,
            //         'payment_status' => $appointment->payment_status,
            //         'status' => $appointment->status,
            //         'created_at' => $appointment->created_at,
            //         'updated_at' => $appointment->updated_at,
            //         'payment_info' => $appointment->payment_info,
            //         'source' => $appointment->source,
            //         'customer' => $appointment->client ? [
            //             'username' => $appointment->client->username ?? null,
            //             'customer_id' => $appointment->client->id ?? null,
            //             'phone' => $appointment->client->phone ?? null,
            //         ] : null,
            //     ];
            // }),
        ];
    }

    private function getImageUrl($path)
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(100));
        }
        return null;
    }

    protected function getFirstService_id(): ?string
    {
        $firstService = $this->appoinment->services->first()?->service;

        return $firstService ? $firstService->id : null;
    }

    protected function getFirstService_ar(): ?string
    {
        $firstService = $this->appoinment->services->first()?->service;

        return $firstService ? $firstService->name_ar : null;
    }
    protected function getFirstService_en(): ?string
    {
        $firstService = $this->appoinment->services->first()?->service;

        return $firstService ? $firstService->name_en : null;
    }
}
