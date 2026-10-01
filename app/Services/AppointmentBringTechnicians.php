<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Sector;
use App\Models\User;
use DateTime;
use Carbon\Carbon;

class AppointmentBringTechnicians
{
    public function get_sectors_techs($sector_id, $requested_date)
    {
        // Get users with the 'technician' role in the specific sector
        // $techs = User::role('فني') // Filter users by 'technician' role
        // ->whereHas('sectors', function ($query) use ($sector_id) {
        //     $query->where('id', $sector_id);
        // })
        // ->get();

        $techs = Sector::where('id', $sector_id)
            ->with(['users' => function ($query) use ($requested_date) {
                $query->whereHas('roles', function ($query) {
                    $query->where('name', '=', 'فني');
                })->with(['schedules' => function ($query) {
                    $query->with('work_shifts');
                }, 'tech_records' => function ($query) use ($requested_date) {
                    $query->where('date', $requested_date);
                }, 'holidays']);
            }])->get();

        $dayName = Carbon::createFromFormat('Y-m-d', $requested_date)->format('l');

        $filteredTechs = $techs->map(function ($sector) use ($dayName) {
            $sector->users = $sector->users->filter(function ($user) use ($dayName) {
                foreach ($user->holidays as $holiday) {
                    $days = json_decode($holiday->days, true);
                    if (in_array(lcfirst($dayName), $days)) {
                        return false;
                    }
                }
                return true;
            });
            return $sector;
        });
        return $filteredTechs[0]->users;
    }

    public function get_sectors_techs_current_appointment($sector_id, $requested_date)
    {
        $techs = $this->get_sectors_techs($sector_id, $requested_date);

        if ($techs->isEmpty()) {
            return []; // Return early if no technicians found
        }

        // Process each technician to add 'record' and 'appointments'
        $processedTechs = $techs->map(function ($tech) use ($requested_date) {
            $record = $tech->tech_records->first()->record ?? 0;
            $appointments = Appointment::where('tech_id', $tech->id)
                ->where('appointment_date', $requested_date)
                ->get()
                ->map(function ($appointment) {
                    return [
                        'start_time' => $appointment->start_time,
                        'end_time' => $appointment->end_time,
                        'appointment_duration' => $appointment->appointment_duration,
                    ];
                });
            return [
                'id' => $tech->id,
                'record' => $record,
                'appointments' => $appointments,
                'schedules' => $tech->schedules,
            ];
        });

        $sortedTechs = $processedTechs->sortBy('record');

        return $sortedTechs;
    }

    public function calculate_techs_time($techs)
    {
        $calculated_time = 0;
        $start_shift = null;
        $end_shift = null;
        if (count($techs) > 0) {
            foreach ($techs as $tech) {
                foreach ($tech['schedules'] as &$schedule) {
                    foreach ($schedule['work_shifts'] as &$shift) {
                        $start = Carbon::createFromTimeString($shift['start_time']);
                        $end = Carbon::createFromTimeString($shift['end_time']);
                        $start_shift = Carbon::createFromTimeString($shift['start_time']);
                        $end_shift = Carbon::createFromTimeString($shift['end_time']);
                        $interval = $end->diffInMinutes($start);
                        $shift['duration'] = $interval;
                        $calculated_time = $interval;
                    }
                }
            }
        }
        return [$calculated_time, $start_shift, $end_shift];
    }

    public function calculate_available_time($techs, $appointment_duration)
    {
        $availableSlots = [];
        $disabledSlots = [];
        $techs = $techs->toArray();
        $techs = array_values($techs);

        foreach ($techs as $tech) {

            $slots = $this->getAvailableSlots($tech);

            if (count($slots[0]) > 0) {
                $userShifts = $slots[0];
            }

            if (count($slots[1]) > 0) {
                foreach ($slots[1] as $slot) {
                    if(!in_array($slot, $disabledSlots))
                    {
                        array_push($disabledSlots, $slot);
                    }
                }
            }

            $seconds = $appointment_duration * 3600;
            $minutesFormatted = gmdate('H:i:s', $seconds);
            foreach ($userShifts as &$shift) {
                $start = Carbon::createFromTimeString($shift['start']);
                $end = Carbon::createFromTimeString($shift['end']);
                $interval = $end->diffInMinutes($start);
                $shift['duration'] = $interval;
                $calculated_time = $interval;

                $possibleTimeSlots = $calculated_time / $appointment_duration;
                $possibleTimeSlots = $possibleTimeSlots / 60;

                $start_shift = $start;
                $end_shift = $end;

                list($hours, $minutes, $seconds) = explode(':', $minutesFormatted);
                $totalMinutes = $hours * 60 + $minutes + $seconds / 60;

                    $startTime = strtotime($start_shift);
                    $endTime = strtotime($end_shift);
                    $bookingDurationInSeconds = $totalMinutes * 60;

                    while ($startTime + $bookingDurationInSeconds <= $endTime) {
                        $endBookingTime = $startTime + $bookingDurationInSeconds;
                        $formattedStart = date('H:i:s', $startTime);
                        $formattedEnd = date('H:i:s', $endBookingTime);
                        $id = $tech['id'];
                        array_push($availableSlots, ['start_time' => $formattedStart, 'end_time' => $formattedEnd, 'status' => 'Available', 'tech_id' => $id]);
                        $startTime = $endBookingTime;
                    }
            }
        }
        // return $availableSlots;
        $disableSerialized = array_map(function ($item) {
            return $item['start_time'];
        }, $disabledSlots);

        $uniqueSerializedDisable = array_unique($disableSerialized);
        $uniqueArraysDisable = array_intersect_key($disabledSlots, $uniqueSerializedDisable);
        $newArrayDisabled = array_values($uniqueArraysDisable);

        $serialized = array_map(function ($item) {
            return $item['start_time'];
        }, $availableSlots);

        $uniqueSerialized = array_unique($serialized);
        $uniqueArrays = array_intersect_key($availableSlots, $uniqueSerialized);
        $newArray = array_values($uniqueArrays);

        $mergedTimes = array_merge($newArray, $newArrayDisabled);

        $organizedTimes = [];
        foreach ($mergedTimes as $time) {
            $start = $time['start_time'];
            if (!isset($organizedTimes[$start]) || $organizedTimes[$start]['status'] === 'Disabled') {
                $organizedTimes[$start] = $time;
            }
        }
        $finalTimes = array_values($organizedTimes);
        return $finalTimes;
    }

    public function getAvailableSlots($tech)
    {
        $availableSlots = collect();
        $disabledSlots = collect();

        if (isset($tech['schedules'])) {
            $schedules = $tech['schedules'];
            $userShifts = $schedules[0]->work_shifts;
            $appointments = $tech['appointments'];
            if (count($appointments) > 0) {
                foreach ($userShifts as $shift) {
                    $shiftStart = Carbon::parse($shift->start_time);
                    $shiftEnd = Carbon::parse($shift->end_time);

                    foreach ($appointments as $appointment) {
                        $appointmentStart = Carbon::parse($appointment['start_time']);
                        $appointmentEnd = Carbon::parse($appointment['end_time']);
                        if ($shiftStart < $appointmentEnd && $shiftEnd > $appointmentStart) {
                            if ($shiftStart < $appointmentStart) {
                                $availableSlots->push([
                                    'start' => $shiftStart->toTimeString(),
                                    'end' => $appointmentStart->toTimeString(),
                                ]);
                            }
                            $shiftStart = $appointmentEnd;
                        }
                    }

                    if ($shiftStart < $shiftEnd) {
                        $availableSlots->push([
                            'start' => $shiftStart->toTimeString(),
                            'end' => $shiftEnd->toTimeString(),
                        ]);
                    }
                }
                foreach ($appointments as $appointment) {
                    $appointmentStart = Carbon::parse($appointment['start_time']);
                    $appointmentEnd = Carbon::parse($appointment['end_time']);
                    if ($appointmentStart) {
                        $disabledSlots->push([
                            'start_time' => $appointmentStart->toTimeString(),
                            'end_time' => $appointmentEnd->toTimeString(),
                            'tech_id' => $tech['id'],
                            'status' => 'Disabled',
                        ]);
                    }
                }
            } else {
                foreach ($userShifts as $shift) {
                    $shiftStart = Carbon::parse($shift->start_time);
                    $shiftEnd = Carbon::parse($shift->end_time);
                    $availableSlots->push([
                        'start' => $shiftStart->toTimeString(),
                        'end' => $shiftEnd->toTimeString(),
                    ]);
                }
            }
        }
        return [$availableSlots, $disabledSlots];
    }

    // public function getAvailableSlots($technicianId, $bookingDuration)
    // {
    //     $technician = Technician::findOrFail($technicianId);
    //     $startShift = Carbon::parse($technician->start_shift);
    //     $endShift = Carbon::parse($technician->end_shift);

    //     // Assume bookingDuration is in minutes
    //     $bookingDurationInMinutes = $bookingDuration;

    //     $existingBookings = Appointment::where('technician_id', $technicianId)
    //     ->whereBetween('booking_time', [$startShift, $endShift])
    //     ->get();

    //     $availableSlots = collect();
    //     $currentSlot = $startShift->copy();

    //     while ($currentSlot->addMinutes($bookingDurationInMinutes)->lte($endShift)) {
    //         $slotEnd = $currentSlot->copy()->addMinutes($bookingDurationInMinutes);

    //         // Check if this slot overlaps with any existing booking
    //         $overlaps = $existingBookings->contains(function ($booking) use ($currentSlot, $slotEnd) {
    //             $bookingStart = Carbon::parse($booking->booking_time);
    //             $bookingEnd = $bookingStart->copy()->addMinutes($booking->duration);

    //             return $slotEnd->greaterThan($bookingStart) && $currentSlot->lessThan($bookingEnd);
    //         });

    //         if (!$overlaps) {
    //             // This slot is available
    //             $availableSlots->push($currentSlot->format('H:i:s') . ' to ' . $slotEnd->format('H:i:s'));
    //         }

    //         // Move to the next possible slot
    //         $currentSlot->addMinutes($bookingDurationInMinutes);
    //     }

    //     return $availableSlots;
    // }
}
