<?php
namespace App\Repositories\Products;

interface ProductRepositoryInterface
{
    public function storeProducts(array $products, int $installation_id);
    public function getProducts(int $perPage = 15, array $filters = []);
    public function getProductByCode(string $stockcode);
    public function updateProductStock(array $stockData);
    public function syncProductsWithTouch365(?int $installationId = null);
}
