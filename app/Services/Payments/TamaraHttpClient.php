<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;

class TamaraHttpClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.tamara.base_url'), '/');
        $this->token   = (string) config('services.tamara.token');
    }

    public function postCheckout(array $payload): array
    {
        $res = Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->post('/checkout', $payload);

        return [
            'ok' => $res->successful(),
            'data' => $res->json(),
            'status' => $res->status(),
        ];
    }
}
