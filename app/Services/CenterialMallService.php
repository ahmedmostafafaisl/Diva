<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use League\OAuth1\Client\Server\Server;
use GuzzleHttp\Exception\RequestException;


class CenterialMallService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://3.73.173.183/wp-json/procontent/v1/',
            // 'base_uri' =>   'https://54.93.52.110//wp-json/procontent/v1/',
            'auth'     => 'oauth', // Enable OAuth authentication
        ]);
    }

    public function getProductsByCategory($categoryId)
    {
        try {
            $oauth = new Oauth1([
                'consumer_key'    =>    env('OAUTH_CONSUMER_KEY'),    // Set in .env file
                'consumer_secret' =>  env('OAUTH_CONSUMER_SECRET'), // Set in .env file
                'token'           => env('OAUTH_ACCESS_TOKEN'),
                'token_secret'    => env('OAUTH_ACCESS_SECRET'),
            ]);

            $this->client->getConfig('handler')->push($oauth);

            $response = $this->client->get('products-by-category-s', [
                'query' => ['category_ids' => $categoryId],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}
