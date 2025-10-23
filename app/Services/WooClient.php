<?php

namespace App\Services;

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class WooClient
{
    protected Client $client;
    protected string $productsJsonUrl = '/wp-content/uploads/products_combined.json';
    protected $creds;

    public function __construct(Client $client, $creds)
    {
        $this->client = $client;
        $this->creds = $creds;
        $this->productsJsonUrl = rtrim($creds->site_url, '/') . $this->productsJsonUrl;
    }

    /**
     * Safe WooCommerce GET wrapper
     */
    protected function safeGet(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->client->get($endpoint, $params);
            return is_array($response) ? $response : [];
        } catch (HttpClientException $e) {
            \Log::error("WooCommerce GET error on {$endpoint}: " . $e->getMessage());
            return [];
        } catch (\Throwable $t) {
            \Log::error("Unexpected GET error on {$endpoint}: " . $t->getMessage());
            return [];
        }
    }

    public function getSiteProductBySku(string $sku): ?array
    {
        try {
            $json = @file_get_contents($this->productsJsonUrl);
            if (!$json) return [];
            $products = json_decode($json, true);
            return $products[$sku] ?? [];
        } catch (\Throwable $th) {
            \Log::error("Error reading local products JSON: " . $th->getMessage());
            return [];
        }
    }

    public function getProductBySku(string $sku): ?array
    {
        $response = $this->safeGet('products', ['sku' => $sku, 'per_page' => 1]);
        return $response[0] ?? [];
    }

    public function createProduct(array $data): array
    {
        try {
            return (array) $this->client->post('products', $data);
        } catch (HttpClientException $e) {
            \Log::error("Error creating product: " . $e->getMessage());
            return [];
        }
    }

    public function updateProduct(int $id, array $data): array
    {
        try {
            return (array) $this->client->put("products/{$id}", $data);
        } catch (HttpClientException $e) {
            \Log::error("Error updating product #{$id}: " . $e->getMessage());
            return [];
        }
    }

    public function getCategoryByName(string $name): ?array
    {
        // Fixed: safe wrapper + detailed error logging
        $response = $this->safeGet('products/categories', ['search' => $name]);

        if (empty($response)) {
            \Log::warning("WooCommerce returned empty/invalid JSON for category search: {$name}");
        }

        return array_map(fn($item) => (array) $item, $response);
    }

    public function createCategory(array $data): array
    {
        try {
            return (array) $this->client->post('products/categories', $data);
        } catch (HttpClientException $e) {
            \Log::error("Error creating category: " . $e->getMessage());
            return [];
        }
    }

    public function updateCategory(int $id, array $data): array
    {
        try {
            return (array) $this->client->put("products/categories/{$id}", $data);
        } catch (HttpClientException $e) {
            \Log::error("Error updating category #{$id}: " . $e->getMessage());
            return [];
        }
    }

    public function createOrder(array $data): array
    {
        try {
            return (array) $this->client->post('orders', $data);
        } catch (HttpClientException $e) {
            \Log::error("Error creating order: " . $e->getMessage());
            return [];
        }
    }

    public function getOrder(int $orderId): array
    {
        return $this->safeGet("orders/{$orderId}");
    }

    public function searchOrders(array $params = []): array
    {
        return array_map(fn($item) => (array) $item, $this->safeGet('orders', $params));
    }

    public function updateOrder(int $orderId, array $data): array
    {
        try {
            return (array) $this->client->put("orders/{$orderId}", $data);
        } catch (HttpClientException $e) {
            \Log::error("Error updating order #{$orderId}: " . $e->getMessage());
            return [];
        }
    }

    public function deleteOrder(int $orderId, bool $force = true): array
    {
        try {
            return (array) $this->client->delete("orders/{$orderId}", ['force' => $force]);
        } catch (HttpClientException $e) {
            \Log::error("Error deleting order #{$orderId}: " . $e->getMessage());
            return [];
        }
    }
}
