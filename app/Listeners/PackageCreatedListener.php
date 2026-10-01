<?php

namespace App\Listeners;

use App\Events\PackageCreated;
use App\Services\Qoyod\QoyodService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PackageCreatedListener
{
    use InteractsWithQueue;
    public function __construct()
    {
        //
    }


    public function handle(PackageCreated $event)
    {
        $package = $event->package;

        $dQ = new QoyodService();
        $dQ->createServiceIfNotExists($package);
    }
}
