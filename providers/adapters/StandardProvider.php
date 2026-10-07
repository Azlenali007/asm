<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Standard SMM Provider API Adapter (JAP/Rental/v2 Standard)
 */

namespace Providers\Adapters;

use Providers\ProviderInterface;
use Core\Logger;
use Exception;

class StandardProvider implements ProviderInterface
{
    private string $apiUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(string $apiUrl, string $apiKey, int $timeout = 30)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    private function request(array $postFields): array
    {
        $postFields['key'] = $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => 'ApexSMM-Engine/3.2 (+https://apexsmm.com)',
        ]);

        $response = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errMsg = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo) {
            Logger::error("Provider cURL Error: [{$errNo}] {$errMsg}", ['url' => $this->apiUrl, 'action' => $postFields['action'] ?? 'unknown']);
            return ['error' => "Provider connection timeout or network error: {$errMsg}"];
        }

        if ($httpCode >= 400) {
            Logger::error("Provider HTTP Error {$httpCode}", ['response' => substr($response ?: '', 0, 200)]);
            return ['error' => "Provider returned HTTP status {$httpCode}"];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            Logger::error("Provider returned invalid JSON", ['raw' => substr($response, 0, 200)]);
            return ['error' => 'Invalid JSON received from provider API.'];
        }

        return $decoded;
    }

    public function services(): array
    {
        return $this->request(['action' => 'services']);
    }

    public function add(array $params): array
    {
        $payload = array_merge(['action' => 'add'], $params);
        return $this->request($payload);
    }

    public function status(string|int $orderId): array
    {
        return $this->request([
            'action' => 'status',
            'order'  => (string)$orderId,
        ]);
    }

    public function multiStatus(array $orderIds): array
    {
        return $this->request([
            'action' => 'status',
            'orders' => implode(',', $orderIds),
        ]);
    }

    public function balance(): array
    {
        return $this->request(['action' => 'balance']);
    }

    public function refill(string|int $orderId): array
    {
        return $this->request([
            'action' => 'refill',
            'order'  => (string)$orderId,
        ]);
    }
}
