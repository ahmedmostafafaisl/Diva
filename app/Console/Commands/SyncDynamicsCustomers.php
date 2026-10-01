<?php

namespace App\Console\Commands;

use App\Models\User;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDynamicsCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dynamics-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'app:sync-dynamics-customers';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        set_time_limit(0); // No time limit
        $failedCustomers = [];
        User::where('type', 'customer')->chunk(50, function ($customers) use (&$failedCustomers) {
            foreach ($customers as $customer) {
                $response = app('App\Services\DynamicsService')
                    ->storeCustomer($customer->dy_id, $customer->username, $customer->phone);
                $response = $response->json();
                if (!isset($response['ResponseStatus']) || !$response['ResponseStatus']) {
                    Log::warning('Failed  customer:', $customer->id);
                    $failedCustomers[] = $customer->id;
                }
            }
        });

        if (!empty($failedCustomers)) {
            Log::warning('Failed to sync customers:', $failedCustomers);
        }

        $this->info('Customer synchronization completed.');
    }
}
