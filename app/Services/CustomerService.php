<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ContactRequestDistribution;
use App\Models\ContactRequestOrder;

class CustomerService
{
    public function CheckIfCustomerService($request)
    {
        $user = $request->user();
        $role = $user->roles[0]->id;
        $skill = $user->skills[0]->id;
        $distribution = ContactRequestDistribution::where('role_id', $role)->where('skill_id', $skill)->first();
        if ($distribution) {
            return true;
        } else {
            return false;
        }
    }

    public function ReplaceCustomerServiceAgent($old_agents, $new_agents)
    {
        $orders_count = 0;
        $old_orders = [];
        foreach ($old_agents as $agent) {
            $orders = ContactRequestOrder::where('user_id', $agent)->where('is_closed', 0)->get();
            if (count($orders) > 0) {
                foreach ($orders as $order) {
                    array_push($old_orders, $order);
                }
                $orders_count = $orders_count + count($orders);
            }
        }

        $new_orders = array_chunk($old_orders,count($new_agents));

        for ($i=0; $i < count($new_orders); $i++) {
            $orderArray = $new_orders[$i];
            foreach ($orderArray as $order) {
                $newOrder = ContactRequestOrder::where('id', $order->id)->first();
                $newOrder->user_id = $new_agents[$i];
                $newOrder->update();
            }
        }
        return response(['message' => 'Orders updated successfully', 'status' => 200]);
    }

}
