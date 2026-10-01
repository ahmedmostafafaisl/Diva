<?php

namespace App\Services;

use App\Models\User;
use App\Models\CarType;
use App\Models\Product;
use App\Models\Service;
use App\Models\CarBrand;
use App\Models\CarModel;
use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class TimeSlotsService
{

    public function getActiveTechnicians($cityId, $districtId, $skillIds, $requestedDate, $dayName): mixed
    {
        return User::where('status', 'active')
            ->where('role', 'technician')
            ->where('city_id', $cityId)
            ->whereHas('districts', function ($query) use ($districtId) {
                $query->where('district_id', $districtId);
            })
            ->whereHas('skills', function ($query) use ($skillIds) {
                $query->whereIn('skill_id', $skillIds);
            })
            ->whereHas('days', function ($query) use ($dayName) {
                $query->where('day_name', $dayName)
                    ->where('status', 'active')
                    ->whereHas('shifts', function ($query) use ($dayName) {
                        $query->whereHas('cityDay', function ($query) use ($dayName) {
                            $query->where('day_name', $dayName);
                        });
                    });
            })
            ->with([
                'skills', // Load associated skills
                'appointments' => function ($query) use ($requestedDate) {
                    $query->where('appointment_date', $requestedDate);
                },
                'tech_records' => function ($query) use ($requestedDate) {
                    $query->where('date', $requestedDate);
                },
                'days' => function ($query) use ($dayName) {
                    $query->where('day_name', $dayName)
                        ->where('status', 'active')
                        ->with(['shifts' => function ($query) use ($dayName) {
                            $query->whereHas('cityDay', function ($query) use ($dayName) {
                                $query->where('day_name', $dayName);
                            });
                        }]);
                }
            ])
            ->withCount(['appointments as appointment_count' => function ($query) use ($requestedDate) {
                $query->whereDate('appointment_date', $requestedDate);
            }])
            ->orderBy('appointment_count', 'desc')
            ->get();
    }

    public function getActiveSlots($cityId, $districtId, $skillIds, $requestedDate, $dayName,  $appointment_duration)
    {
        $timeZone = new CarbonTimeZone('Asia/Riyadh');
        $now = Carbon::now($timeZone);

        // Format the current time to include hours, minutes, and seconds
        $formattedNow = $now->format('H:i:s');

        $technicians = $this->getActiveTechnicians($cityId, $districtId, $skillIds, $requestedDate, $dayName);

        $availableSlots = collect();
        $disabledSlots = collect();
        // Process each technician to find available and disabled slots
        foreach ($technicians as $technician) {
            $appointments = $this->single_tech_appointmentsCount($requestedDate, $technician->id);
            $shifts = $technician->days->flatMap->shifts;
            foreach ($shifts as $shift) {
                $shiftStart = Carbon::parse($requestedDate . ' ' . $shift->start_time);
                $shiftEnd = Carbon::parse($requestedDate . ' ' . $shift->end_time);
                // Adjust for shifts that end after midnight
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }
                $currentStart = $shiftStart->copy();
                // Sort appointments by start time to ensure correct slot generation
                $sortedAppointments = $appointments->sortBy('start_time');
                foreach ($sortedAppointments as $appointment) {
                    $appointmentStart = Carbon::parse($requestedDate . ' ' . $appointment->start_time);
                    $appointmentEnd = Carbon::parse($requestedDate . ' ' . $appointment->end_time);
                    // Adjust for appointments that end after midnight
                    if ($appointmentEnd->lt($appointmentStart)) {
                        $appointmentEnd->addDay();
                    }
                    // Capture available time before the appointment starts
                    if ($currentStart < $appointmentStart && $appointmentStart <= $shiftEnd) {
                        $availableSlots->push([
                            'start_time' => $currentStart->toDateTimeString(),
                            'end_time' => $appointmentStart->toDateTimeString(),
                            'tech_id' => $technician->id,
                            'status' => 'Available'
                        ]);
                    }
                    // Move the current start time to the end of the appointment if within shift
                    if ($currentStart <= $appointmentEnd && $appointmentEnd <= $shiftEnd) {
                        $currentStart = $appointmentEnd;
                    }
                }
                // Capture remaining available time after the last appointment
                if ($currentStart < $shiftEnd) {
                    $availableSlots->push([
                        'start_time' => $currentStart->toDateTimeString(),
                        'end_time' => $shiftEnd->toDateTimeString(),
                        'tech_id' => $technician->id,
                        'status' => 'Available'
                    ]);
                }
            }
        }
        $eligibleSlots = [];
        $requiredDuration = $appointment_duration;
        foreach ($availableSlots as $slot) {
            $startTime = Carbon::parse($slot['start_time']);
            $endTime = Carbon::parse($slot['end_time']);
            $duration = $endTime->diffInMinutes($startTime);
            // Check if the slot duration is enough for the required service duration
            if ($duration >= $requiredDuration && $slot['status'] === 'Available') {
                $eligibleSlots[] = [
                    'start_time' => $startTime->format('H:i:s'), // Format as hours, minutes, seconds
                    'end_time' => $endTime->format('H:i:s'),
                    'tech_id' => $slot['tech_id'],
                    'status' => $slot['status'],
                ];
            }
        }
        $eligibleSlots;
        $dividedSlots = [];
        foreach ($eligibleSlots as $slot) {
            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $slot['start_time']);
            $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $slot['end_time']);
            // Loop to divide slot by $requiredDuration
            while ($startTime->lt($endTime)) {
                $subSlotEndTime = $startTime->copy()->addMinutes($requiredDuration);
                // Check if the next end time exceeds the slot's end time
                if ($subSlotEndTime->gt($endTime)) {
                    break;
                }
                // Append the sub-slot to the divided slots array
                $dividedSlots[] = [
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $subSlotEndTime->format('H:i:s'),
                    'tech_id' => $slot['tech_id'],
                    'status' => $slot['status'],
                ];
                // Move start time to the next sub-slot
                $startTime = $subSlotEndTime;
            }
        }
        $tempSlots = [];
        $dividedSlots = [];
        // Loop to divide slots by required duration
        foreach ($eligibleSlots as $slot) {
            $startTime = Carbon::createFromFormat('H:i:s', $slot['start_time']);
            $endTime = Carbon::createFromFormat(
                'H:i:s',
                $slot['end_time']
            );
            while ($startTime->lt($endTime)) {
                $subSlotEndTime = $startTime->copy()->addMinutes($requiredDuration);
                if ($subSlotEndTime->gt($endTime)) {
                    break;
                }
                // Append the divided slot to a temporary array with 'start_time' and 'end_time' as the key
                $key = $startTime->format('H:i:s') . '-' . $subSlotEndTime->format('H:i:s');
                $tempSlots[$key][] = [
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $subSlotEndTime->format('H:i:s'),
                    'tech_id' => $slot['tech_id'],
                    'status' => $slot['status'],
                ];
                $startTime = $subSlotEndTime;
            }
        }
        // Filter the slots based on minimum appointments
        foreach ($tempSlots as $timeKey => $slots) {
            $minAppointments = PHP_INT_MAX;
            $selectedSlot = null;
            foreach ($slots as $slot) {
                $techId = $slot['tech_id'];
                $app =  $this->single_tech_appointmentsCount($requestedDate, $techId) ?? PHP_INT_MAX;
                $appointments =    $app->count();
                // Select the slot with the fewest appointments
                if ($appointments < $minAppointments) {
                    $minAppointments = $appointments;
                    $selectedSlot = $slot;
                }
            }
            // Add the selected slot to the final dividedSlots array
            if ($selectedSlot) {
                $dividedSlots[] = $selectedSlot;
            }
        }
        // return $dividedSlots;
        // Parse the requested date to a Carbon instance
        $requestedDate = Carbon::parse($requestedDate, $timeZone);

        if ($requestedDate->isToday()) {
            // If the requested date is today, filter slots after 'now'
            $filteredSlots = collect($dividedSlots)->filter(function ($slot) use ($now) {
                return Carbon::createFromFormat('H:i:s', $slot['start_time'])->greaterThan($now);
            })->sortBy('start_time')->values();
        } elseif ($requestedDate->isFuture()) {
            // If the requested date is in the future, return all slots sorted by start_time
            $filteredSlots = collect($dividedSlots)->sortBy('start_time')->values();
        } else {
            // Handle past dates if necessary (optional)
            $filteredSlots = collect(); // Empty collection for past dates
        }

        return $filteredSlots;
        return  collect($dividedSlots)->sortBy('start_time')->values()->all();
    }
    public function single_tech_appointmentsCount($date, $id)
    {
        $appointments = Appointment::where('appointment_date', $date)->where('tech_id', $id)->get();
        return $appointments;
    }
}
