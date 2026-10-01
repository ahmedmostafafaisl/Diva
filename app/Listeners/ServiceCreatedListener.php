<?php

namespace App\Listeners;

use App\Events\ServiceCreated;
use App\Services\Qoyod\QoyodService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ServiceCreatedListener
{
    use InteractsWithQueue;
    public function __construct()
    {
        //
    }

    public function handle(ServiceCreated $event)
    {
        $service = $event->service;

        $dQ = new QoyodService();
        $dQ->createServiceIfNotExists($service);
    }
}
