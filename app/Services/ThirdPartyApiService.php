<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class ThirdPartyApiService
{
    protected $client;

    public function __construct()
    {
        $stack = HandlerStack::create();

        $middleware = new Oauth1([
            'consumer_key'    => env('OAUTH_CONSUMER_KEY'),
            'consumer_secret' => env('OAUTH_CONSUMER_SECRET'),
            'token'           => env('OAUTH_TOKEN'),
            'token_secret'    => env('OAUTH_TOKEN_SECRET'),
        ]);

        $stack->push($middleware);

        $this->client = new Client([
            'base_uri' => 'https://centerialmall.com/wp-json/procontent/v1/',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
                'Referer' => 'https://centerialmall.com/',
            ],
        ]);
    }



    public function getProductsByCategory(array $categoryIds)
    {
        // try {
        $response = $this->client->get('products-by-category-s', [
            'query' => [
                'category_ids' => implode(',', $categoryIds),
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
        // } catch (\Exception $e) {
        //     return [
        //         'error'   => true,
        //         'message' => $e->getMessage(),
        //     ];
        // }
    }
}
