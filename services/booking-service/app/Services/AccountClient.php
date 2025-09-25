<?php
namespace App\Services;

use GuzzleHttp\Client;

class AccountClient
{
    private Client $http;
    private string $baseUrl;

    public function __construct(?Client $http = null)
    {
        $this->baseUrl = rtrim(getenv('ACCOUNT_SERVICE_URL') ?: 'http://account-service:8000', '/');
        $this->http = $http ?: new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 2.0,
        ]);
    }

    public function getAccountMinById(int $id): ?array
    {
        try {
            $resp = $this->http->get('/api/internal/accounts/' . $id);
            if ($resp->getStatusCode() !== 200) return null;
            $data = json_decode((string) $resp->getBody(), true);
            if (!is_array($data)) return null;
            return [
                'id' => $data['id'] ?? $id,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
            ];
        } catch (\Throwable $e) {
            return null; // fail-soft for downstream outages
        }
    }
}
