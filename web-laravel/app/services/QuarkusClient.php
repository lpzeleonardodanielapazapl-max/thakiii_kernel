<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuarkusClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.quarkus.url');
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(15);
    }

    public function get(string $endpoint): array
    {
        try {
            $response = $this->client()->get($endpoint);
            if ($response->failed()) {
                Log::error("Quarkus GET {$endpoint}", ['status' => $response->status()]);
                return [];
            }
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("Quarkus GET {$endpoint}", ['msg' => $e->getMessage()]);
            return [];
        }
    }

    public function post(string $endpoint, array $data = []): array
    {
        try {
            return $this->client()->post($endpoint, $data)->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("Quarkus POST {$endpoint}", ['msg' => $e->getMessage()]);
            return [];
        }
    }

    public function put(string $endpoint, array $data = []): array
    {
        try {
            return $this->client()->put($endpoint, $data)->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("Quarkus PUT {$endpoint}", ['msg' => $e->getMessage()]);
            return [];
        }
    }

    public function delete(string $endpoint): array
    {
        try {
            return $this->client()->delete($endpoint)->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("Quarkus DELETE {$endpoint}", ['msg' => $e->getMessage()]);
            return [];
        }
    }
}