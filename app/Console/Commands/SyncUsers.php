<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Address2;
use Illuminate\Console\Command;
use App\Services\WooOrderService;


class SyncUsers extends Command
{
    protected $signature = 'sync:users';
    protected $description = 'Sync users with external WooService';

    protected WooOrderService $wooService;

    public function __construct(WooOrderService $wooService)
    {
        parent::__construct();
        $this->wooService = $wooService;
    }

    public function handle()
    {
        $this->info("Starting user sync...");

        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                try {
                    $data = $user->toArray();
                    $data['phone'] = str_replace('+', '', $user->phone);

                    $address = Address2::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->where('default', true)
                        ->first() ?? [];

                    $customer = $this->wooService->getUserByPhone(
                        $user->email,
                        $user->phone,
                        $user,
                        $address
                    );

                    if ($customer) {
                        if (isset($customer['role'])) {
                            $user->role = $customer['role'];
                        }

                        if (array_key_exists('is_private', $customer)) {
                            $user->is_private = (bool) $customer['is_private'];
                        }

                        $user->save();
                    }
                } catch (\Throwable $e) {
                    \Log::error("Failed to sync user ID {$user->id}: " . $e->getMessage());
                }
            }
        });

        $this->info("User sync complete.");
    }
}
