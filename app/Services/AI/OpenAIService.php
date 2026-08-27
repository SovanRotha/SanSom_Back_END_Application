<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use OpenAI;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $httpClient = new Client([
            'verify' => config('services.openai.ca_bundle') ?: true,
        ]);

        $this->client = OpenAI::factory()
            ->withApiKey(config('services.openai.api_key'))
            ->withBaseUri(config('services.openai.base_uri'))
            ->withHttpHeader('HTTP-Referer', config('services.openai.http_referer', 'http://localhost'))
            ->withHttpHeader('X-Title', config('services.openai.app_title', config('app.name')))
            ->withHttpClient($httpClient)
            ->make();
    }

    public function chat(array $messages)
    {
        return $this->client->chat()->create([
            'model' => config('services.openai.model'),

            'messages' => $messages,
        ]);
    }
}