<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Appointment;
use App\Models\Subscription;
use Carbon\Carbon;

class GetNotPaidAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get not-paid appointments created from 10 minutes ago until now';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $now = Carbon::now();
        $tenMinutesAgo = $now->subMinutes(10);
        $appointments = Appointment::whereIn('payment_type', ['tamara', 'tabby'])
            ->where('payment_status', 'pending')
            ->where('created_at', '<=', $tenMinutesAgo)->get();
        foreach ($appointments as $appointment) {
            // Example: Log the appointment details (or do any other action)
            $appointment->update(['payment_status' => 'unpaid_status']);
        }

        // subscriptions


        $subscriptions = Subscription::whereIn('payment_type', ['tamara', 'tabby'])
            ->where('payment_status', 'pending')
            ->where('created_at', '<=', value: $tenMinutesAgo)->get();

        foreach ($subscriptions as $subscription) {
            // Example: Log the appointment details (or do any other action)
            $subscription->update(['payment_status' => 'unpaid_status']);
        }

        // Indicate success
        $this->info('Retrieved ' . $appointments->count() . ' appointments created in the last 10 minutes.');
        $this->info('Retrieved ' . $subscriptions->count() . ' appointments created in the last 10 minutes.');
    }
}
