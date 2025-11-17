<?php
namespace App\Services;

use Automattic\WooCommerce\Client;

class WooClient
{
    protected Client $client;
    protected string $productsJsonUrl = '/wp-content/uploads/products_combined.json';
    protected $creds;

    public function __construct(Client $client, $creds)
    {
        $this->client = $client;
        $this->creds = $creds;
        $this->productsJsonUrl = $creds->site_url. $this->productsJsonUrl;
    }

    public function getSiteProductBySku(string $sku): ?array
    {
        try {
            $json = file_get_contents($this->productsJsonUrl);
            $products = json_decode($json, true);

            return $products[$sku] ?? [];
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function searchProductBySku(string $sku): array
    {
        return (array) $this->client->get('products', ['sku' => $sku]);
    }
    
    public function getCredentials()
    {
        return $this->creds;
    }

    public function getProductBySku(string $sku): ?array
    {
        $response = $this->client->get('products', ['sku' => $sku, 'per_page' => 1]);
        return $response[0] ?? [];
    }

    public function createProduct(array $data): array
    {
        return (array) $this->client->post('products', $data);
    }

    public function updateProduct(int $id, array $data): array
    {
        return (array) $this->client->put("products/{$id}", $data);
    }

    public function getProductVariations(int $parentId): array
    {
        return array_map(fn($item) => (array) $item, (array) $this->client->get("products/{$parentId}/variations", ['per_page' => 100]));
    }

    public function createProductVariation(int $parentId, array $data): array
    {
        return (array) $this->client->post("products/{$parentId}/variations", $data);
    }

    public function updateProductVariation(int $parentId, int $variationId, array $data): array
    {
        return (array) $this->client->put("products/{$parentId}/variations/{$variationId}", $data);
    }

    public function getCategoryByName(string $name): ?array
    {
        $response = $this->client->get('products/categories', ['search' => $name]);

        return is_array($response) ? array_map(fn($item) => (array) $item, $response) : [];
    }

    public function createCategory(array $data): array
    {
        return (array) $this->client->post('products/categories', $data);
    }

    public function updateCategory(int $id, array $data): array
    {
        return (array) $this->client->put("products/categories/{$id}", $data);
    }

    public function batchProductAttributeTerms(int $attributeId, array $batchData): array
    {
        return (array) $this->client->post("products/attributes/{$attributeId}/terms/batch", $batchData);
    }

    /**
     * Create an order in WooCommerce
     */
    public function createOrder(array $data): array
    {
        return (array) $this->client->post('orders', $data);
    }

    /**
     * Retrieve a single order by ID
     */
    public function getOrder(int $orderId): array
    {
        return (array) $this->client->get("orders/{$orderId}");
    }

    /**
     * Search orders by criteria (e.g. customer, status)
     */
    public function searchOrders(array $params = []): array
    {
        return array_map(fn($item) => (array) $item, (array) $this->client->get('orders', $params));
    }

    /**
     * Update an order
     */
    public function updateOrder(int $orderId, array $data): array
    {
        return (array) $this->client->put("orders/{$orderId}", $data);
    }

    /**
     * Delete an order
     */
    public function deleteOrder(int $orderId, bool $force = true): array
    {
        return (array) $this->client->delete("orders/{$orderId}", ['force' => $force]);
    }

}
