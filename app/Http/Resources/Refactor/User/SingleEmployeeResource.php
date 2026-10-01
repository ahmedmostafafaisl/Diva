<?php

namespace App\Http\Resources\Refactor\User;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Appointment\AppointmentResource;
use App\Http\Resources\Refactor\Appointment\DashAppointmentResource;

class SingleEmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->getAppointmentCountsSummary($this->id);

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'status' => $this->status,
            'following_id' => $this->following_id,
            "following_name" => $this->leader->username ?? null,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'city' => $this->city ?? null,
            'districts' => $this->districts ?? null,
            'skills' => $this->skills,
            'team' => UserWithAttachResource::collection($this->technicians),
            'days' => EmpDaysResource::collection($this->days),
            'appointments' =>  AppointmentResource::collection($this->appointments),
            'summary' => $summary, // Add the summary data here
        ];
    }

    private function getAppointmentCountsSummary($tech_id): array
    {
        // Total Appointments
        $totalAppointments = Appointment::where('tech_id', $tech_id)->count();

        // Total Cancelled Appointments
        $totalCancelledAppointments = Appointment::where('tech_id', $tech_id)
            ->whereIn('status', [
                'cancelled_by_cs',
                'cancelled_by_tech',
                'cancelled_by_customer',
            ])
            ->count();

        // Total Rescheduled Appointments
        $totalRescheduledAppointments = Appointment::where('tech_id', $tech_id)
            ->whereIn('status', [
                'rescheduled_by_tech',
                'rescheduled_by_customer',
                'rescheduled_by_cs',
            ])
            ->count();

        // Total Completed Appointments
        $totalCompletedAppointments = Appointment::where('tech_id', $tech_id)
            ->where('status', 'completed')
            ->count();

        return [
            'total_appointments' => $totalAppointments,
            'total_cancelled_appointments' => $totalCancelledAppointments,
            'total_rescheduled_appointments' => $totalRescheduledAppointments,
            'total_completed_appointments' => $totalCompletedAppointments,
        ];
    }
}
