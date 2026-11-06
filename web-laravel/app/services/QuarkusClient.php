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
        return Http::baseUrl($this->baseUrl)->withToken((string) session('api_token', ''))
            ->acceptJson()
            ->timeout(15);
    }

    public function get(string $endpoint): array
    {
        return $this->client()->get($endpoint)->throw()->json() ?? [];
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
