<?php

namespace App\Services;

use App\Models\ContactRequest;
use App\Models\ContactRequestDistribution;
use App\Models\ContactRequestOrder;
use App\Models\ContactRequestStatus;
use App\Models\User;
use App\Models\UserWorkSchedule;
use App\Models\WorkSchedule;
use Carbon\Carbon;

class OrderRoundService
{
    protected $checkClientExistence;

    public function __construct(CheckClientExistence $checkClientExistence)
    {
        $this->checkClientExistence = $checkClientExistence;
    }

    public function UserNewOrderRound($user_id)
    {
        $user = User::where('id', $user_id)->with('roles', 'skills')->first();
        if (count($user->roles) < 0) {
            return response(['message' => 'user does not have any roles']);
        }

        if (count($user->skills) < 0) {
            return response(['message' => 'user does not have any skills']);
        }

        $schedule = UserWorkSchedule::where('user_id', $user->id)->first();

        if (!$schedule) {
            return response(['message' => 'user does not have any Work Schedule']);
        }

        $schedule = WorkSchedule::where('id', $schedule->work_schedule_id)->with('work_shifts')->first();
        $shifts = $schedule->work_shifts;

        $distribution = ContactRequestDistribution::select('qty', 'req_type')->where('role_id', $user->roles[0]->id)->where('skill_id', $user->skills[0]->id)->first();
        $qty = $distribution->qty;
        $current_shift = [];
        $currentTime = Carbon::now();

        foreach ($shifts as $shift) {
            $startTime = Carbon::parse($shift['start_time']);
            $endTime = Carbon::parse($shift['end_time']);

            if ($currentTime->between($startTime, $endTime)) {
                array_push($current_shift, $shift);
                break;
            }
        }

        if (count($current_shift) > 0) {

            $current_shift = $current_shift[0];
            $user_requests =  ContactRequestOrder::where('user_id', $user_id)->where('is_closed', 0)->with('status', 'sub')->get();

            $user_done_requests = $user_requests->whereBetween('updated_at', [Carbon::parse($current_shift->start_time), Carbon::parse($current_shift->end_time)])
                ->where('user_id', $user_id);

            $user_done_requests = $user_requests->filter(function ($request) {
                return Carbon::parse($request->updated_at)->isToday();
            })->count();

            if ($user_requests === $qty) {
                return response(['in_shift' => true, 'still_available' => false, 'finished' => $user_done_requests, 'from' => $qty]);
            } else {
                $current_order = $user_requests->where('current_order', 1)->first();
                if (!$current_order) {
                    foreach ($user_requests as $request) {
                        $status = ContactRequestStatus::find($request->contact_request_status_id);
                        // return $status;
                        $lastUpdate = Carbon::parse($request->updated_at);
                        $minutes = $status->time;
                        // $minutes = (int)$minutes;
                        $currentDateTime = Carbon::now();
                        if ($lastUpdate->diffInMinutes($currentDateTime) >= $minutes) {
                            $contact_request = $this->checkClientExistence->RequestWithClientCheck($request->contact_request_id);

                            $order = ContactRequestOrder::find($request->id);
                            $order->current_order = 1;
                            $order->update();

                            $request->contact_request = $contact_request->original;

                            return response(['in_shift' => true, 'still_available' => true, 'finished' => $user_done_requests, 'from' => $qty, 'order' => $request]);
                            break;
                        }
                    }

                    $contact_request = ContactRequest::where('req_type', $distribution->req_type)->where('moved', 0)->orderBy('created_at', 'asc')->first();
                    $order = ContactRequestOrder::create([
                        'user_id' => $user_id,
                        'contact_request_id' => $contact_request->id,
                        'client_type' => $contact_request->client_type,
                        'order_type' => $contact_request->req_type,
                        'current_order' => 1,
                    ]);
                    $contact_request->moved = 1;
                    $contact_request->update();
                    $order = ContactRequestOrder::where('id', $order->id)->first();
                    $order->contact_request = $this->checkClientExistence->RequestWithClientCheck($contact_request->id);
                    $order->contact_request = $order->contact_request->original;
                    return response(['in_shift' => true, 'still_available' => true, 'finished' => $user_done_requests, 'from' => $qty, 'order' => $order]);
                } else {
                    $contact_request = $this->checkClientExistence->RequestWithClientCheck($current_order->contact_request_id);
                    $current_order->contact_request = $contact_request->original;
                    return response(['in_shift' => true, 'still_available' => true, 'finished' => $user_done_requests, 'from' => $qty, 'order' => $current_order]);
                }
            }
        } else {
            $current_order = ContactRequestOrder::where('user_id', $user_id)->where('is_closed', 0)->where('current_order', 1)->with('status')->first();
            if ($current_order) {
                $contact_request = $this->checkClientExistence->RequestWithClientCheck($current_order->contact_request_id);
                $current_order->contact_request = $contact_request->original;

                return response(['in_shift' => false, 'order' => $current_order]);
            } else {
                return response(['in_shift' => false]);
            }
        }

        return $qty;
    }
}
