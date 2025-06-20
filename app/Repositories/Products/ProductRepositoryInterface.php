<?php
namespace App\Repositories\Products;

interface ProductRepositoryInterface
{
    public function storeProducts(array $products, ?string $doamin = null);
    public function getProducts(int $perPage = 15, array $filters = []);
    public function getProductByCode(string $stockcode);
    public function updateProductStock(array $stockData);
    public function syncProductsWithTouch365(?int $installationId = null);
}
