<?php

namespace WeLabs\AzureScout;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AzureSearchClient {
    private string $baseUrl;
    private string $apiKey;
    private Client $httpClient;
    private string $apiVersion = '2024-07-01';

    public function __construct(string $baseUrl, string $apiKey) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key' => $this->apiKey,
            ],
        ]);
    }

    public function uploadDocuments(string $indexName, array $documents): array {
        $endpoint = "/indexes/{$indexName}/docs/index?api-version={$this->apiVersion}";
        $body = json_encode(['value' => $documents]);
        $response = $this->httpClient->post($endpoint, ['body' => $body]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function search(string $indexName, array $query): array {
        $endpoint = "/indexes/{$indexName}/docs/search?api-version={$this->apiVersion}";
        $body = json_encode($query);
            $response = $this->httpClient->post($endpoint, ['body' => $body]);
            return json_decode($response->getBody()->getContents(), true);
      
    }

    public function createIndex(string $indexName, array $definition): array {
        $endpoint = "/indexes/{$indexName}?api-version={$this->apiVersion}";
        $body = json_encode($definition);

        $response = $this->httpClient->put($endpoint, ['body' => $body]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteIndex(string $indexName): array {
        $endpoint = "/indexes/{$indexName}?api-version={$this->apiVersion}";
        $response = $this->httpClient->delete($endpoint);

        return [
            'success' => $response->getStatusCode() === 204,
        ];
    }

    public function createOrUpdateIndex(string $indexName, array $definition): array {
        $endpoint = "/indexes/{$indexName}?allowIndexDowntime=false&api-version={$this->apiVersion}";
        $body = json_encode($definition);

        $response = $this->httpClient->put($endpoint, ['body' => $body]);
        return json_decode($response->getBody()->getContents(), true);
    }
} 