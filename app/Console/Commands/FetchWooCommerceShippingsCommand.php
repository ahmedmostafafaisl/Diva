<?php


namespace App\Console\Commands;

use App\Models\Zone;
use App\Models\Shipping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Jobs\FetchWooCommerceShippings;

class FetchWooCommerceShippingsCommand extends Command
{
    protected $signature = 'woocommerce:fetch-shippings';


    protected $description = 'Fetch and store all WooCommerce shipping methods for all zones';


    public function handle()
    {
        $apiUrl = 'https://3.73.173.183/wp-json/wc/v3/shipping/zones';
        $consumerKey = 'ck_3ac0df7ec455715d7173bef920c7a779ac907c51';
        $consumerSecret = 'cs_03ea5e697c1d25a77c5f578cf8a88593ba3c1d4e';

        $zones = Zone::all();

        foreach ($zones as $zone) {
            $zoneId = $zone->id;

            $response = Http::withOptions([
                'verify' => false
            ])->withBasicAuth($consumerKey, $consumerSecret)
                ->get("{$apiUrl}/{$zoneId}/methods");

            if ($response->successful()) {
                foreach ($response->json() as $shipping) {
                    Shipping::updateOrCreate(
                        ['id' => $shipping['id']],
                        [
                            'zone_id' => $zoneId,
                            'instance_id' => $shipping['instance_id'],
                            'title' => $shipping['title'] ?? '',
                            'method_title' => $shipping['method_title'] ?? '',
                            'method_description' => $shipping['method_description'] ?? '',
                            'order' => $shipping['order'] ?? null,
                            'enabled' => $shipping['enabled'] ?? false,
                            'cost' => $shipping['settings']['cost']['value'] ?? 0,
                            'tax' => $shipping['settings']['tax_status']['value'] ?? 0,
                        ]
                    );
                }

                $this->info("Shipping methods for zone ID {$zoneId} stored successfully.");
            } else {
                $this->error("Failed to fetch shipping methods for zone ID {$zoneId}: " . $response->body());
            }
        }

        $this->info('All shipping methods fetched and stored.');
    }
}
