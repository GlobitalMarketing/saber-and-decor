<?php

namespace App\Services;

use App\Factories\WooClientFactory;
use App\Services\ProductExportService;
use App\Services\WooSync\LogService;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class SyncService
{
    protected ProductExportService $productsService;
    protected LogService $logService;
    protected $woocommerce;

    protected string $imageSourceUrl;

    protected int $colorIndex;
    protected string $colorName;
    protected int $sizeIndex;
    protected string $sizeName;

    protected bool $childCategoryIndexing = true;
    protected bool $resetEntries = true;
    protected bool $assignDefaultVariation = false;
    protected bool $includeImages = false;
    protected bool $assignSingleImage = true;
    protected bool $updateImages = false;
    protected bool $updateOnly = false;

    public function __construct(ProductExportService $productsService, LogService $logService)
    {
        $this->productsService = $productsService;
        $this->logService = $logService;
    }

    public function checkAndSyncProducts(int $installationId): void
    {
        ini_set('memory_limit', '1G');
        set_time_limit(600);

        $this->woocommerce = WooClientFactory::make($installationId);
        $creds = $this->woocommerce->getCredentials();
        $tenantFolder = $creds->tenant ?? 'default';
        $this->imageSourceUrl = rtrim(config('app.image_source_url'), '/') . "/{$tenantFolder}/";

        $this->colorIndex = $creds->colorIndex;
        $this->colorName = $creds->colorName;
        $this->sizeIndex = $creds->sizeIndex;
        $this->sizeName = $creds->sizeName;

        $this->childCategoryIndexing = $creds->child_category_indexing;
        $this->resetEntries = $creds->reset_entries;
        $this->assignDefaultVariation = $creds->assign_default_variation;
        $this->includeImages = $creds->include_images;
        $this->assignSingleImage = $creds->assign_single_image;
        $this->updateImages = $creds->update_images;
        $this->updateOnly = $creds->update_only;


        $this->logService->write('Import Started..');

        $posProducts = $this->productsService->fetchDistinctProducts();

        if (empty($posProducts['product_count']) || $posProducts['product_count'][0] <= 0) {
            echo 'No products found in the POS DB.';
            return;
        }
        
        foreach ($posProducts['products'] as $sku => $productGroup) {
            $this->processProductGroup($sku, $productGroup);
        }

        $this->logService->write('Import Complete.');
        echo 'Import Complete...';
    }

    protected function processProductGroup(string $sku, array $productGroup): void
    {
        $categoryId = $this->resolveCategories($productGroup[0]);
        $siteProduct = $this->woocommerce->getSiteProductBySku($sku);
        
        if (!$siteProduct) {
            $this->createNewProduct($sku, $productGroup, $categoryId);
        } else {
            $this->updateExistingProduct($siteProduct, $productGroup, $categoryId);
        }
    }

    protected function createNewProduct(string $sku, array $productGroup, ?array $categoryId): void
    {
        if (count($productGroup) > 1) {
            $this->logService->write("{$sku} does not exist, creating variable parent..");

            $attributes = $this->prepareVariationAttributes($productGroup);
            $parent = $this->createVariableParent($productGroup[0], $attributes, $categoryId);
            if ($this->resetEntries) {
                $this->productsService->resetProduct($productGroup[0]['sku']);
            }
            if (isset($parent['id'])) {
                foreach ($productGroup as $variation) {
                    $this->logService->write("{$variation['sku']} does not exist, creating variation..");
                    $this->createVariation($parent['id'], $variation);
                    if ($this->resetEntries) {
                        $this->productsService->resetProduct($variation['sku']);
                    }
                }
            }
        } else {
            $this->logService->write("{$sku} does not exist, creating simple product..");
            $this->createSimpleProduct($productGroup[0], $categoryId);
            if ($this->resetEntries) {
                $this->productsService->resetProduct($productGroup[0]['sku']);
            }
        }
    }

    protected function updateExistingProduct(array $siteProduct, array $productGroup, ?array $categoryId): void
    {
        if ($this->updateOnly === false) return;
        
        $attributes = $this->prepareVariationAttributes($productGroup);
        $updated = $this->updateVariableParent($siteProduct['ID'], $productGroup[0], $attributes, $categoryId);
        $this->logService->write("{$updated['sku']} updated successfully");
        foreach ($productGroup as $variation) {
            if (!isset($siteProduct['variations'][$variation['sku']])) {
                $this->logService->write("{$variation['sku']} not found, creating variation..");
                $this->createVariation($siteProduct['ID'], $variation);
            } else {
                $this->logService->write("{$variation['sku']} exists, updating variation..");
                $variationId = $siteProduct['variations'][$variation['sku']]['ID'];
                $this->updateVariation($siteProduct['ID'], $variationId, $variation);
                
            }
            if ($this->resetEntries) {
                $this->productsService->resetProduct($variation['sku']);
            }
        }
        
        if ($this->resetEntries) {
            $this->productsService->resetProduct($productGroup[0]['sku']);
        }
    }

    protected function resolveCategories(array $product): ?array
    {
        $parentId = null;
        $categoryIds = [];
        if (!empty($product['category_name'])) {
            $parent = $this->woocommerce->getCategoryByName($product['category_name']);
            $parentId = $parent && isset($parent[0]) ? $parent[0]['id'] : $this->woocommerce->createCategory(['name' => $product['category_name']])['id'];
            $categoryIds[] = $parentId;
        }

        if ($this->childCategoryIndexing && !empty($product['sub_category_name'])) {
            $child = $this->woocommerce->getCategoryByName($product['sub_category_name']);
            if ($child && isset($child[0])) {
                $this->woocommerce->updateCategory($child[0]['id'], ['parent' => $parentId]);
                $categoryIds[] = $child[0]['id'];
            } else {
                $childId = $this->woocommerce->createCategory(['name' => $product['sub_category_name'], 'parent' => $parentId])['id'];
                $categoryIds[] = $childId;
            }
        }

        return $categoryIds;
    }

    protected function prepareVariationAttributes(array $productGroup): array
    {
        $colors = [];
        $sizes = [];

        foreach ($productGroup as $item) {
            if (!in_array($item['color'], $colors)) {
                $colors[] = $item['color'];
            }
            $sizes[] = ($item['size'] === 'ZZZ') ? 'GENERAL' : $item['size'];
        }

        return [
            [
                'id' => $this->colorIndex,
                'name' => $this->colorName,
                'visible' => true,
                'variation' => true,
                'options' => array_unique($colors)
            ],
            [
                'id' => $this->sizeIndex,
                'name' => $this->sizeName,
                'visible' => true,
                'variation' => true,
                'options' => array_unique($sizes)
            ]
        ];
    }

    protected function createSimpleProduct(array $product): array
    {
        $data = $this->buildSimpleProductData($product);

        try {
            $product = $this->woocommerce->createProduct($data);
            $this->logService->write('New product created: ' . $product['id']);
            return $product;
        } catch (HttpClientException $e) {
            $this->logService->write("Error creating simple product: {$e->getMessage()}");
            return [];
        }
    }

    protected function createVariableParent(array $product, array $attributes, ?array $categoryId): array
    {
        $data = $this->buildVariableParentData($product, $attributes, $categoryId);

        try {
            return $this->woocommerce->createProduct($data);
        } catch (HttpClientException $e) {
            $this->logService->write("Error creating variable parent: {$e->getMessage()}");
            return [];
        }
    }

    protected function createVariation(int $parentId, array $variation): array
    {
        $data = $this->buildVariationData($variation);

        try {
            return $this->woocommerce->createProductVariation($parentId, $data);
        } catch (HttpClientException $e) {
            $this->logService->write("Error creating variation: {$e->getMessage()}");
            return [];
        }
    }

    protected function updateVariableParent(int $parentId, array $product, array $attributes, ?array $categoryId): array
    {
        $data = $this->buildVariableParentData($product, $attributes, $categoryId);

        try {
            return $this->woocommerce->updateProduct($parentId, $data);
        } catch (HttpClientException $e) {
            $this->logService->write("Error updating parent: {$e->getMessage()}");
            return [];
        }
    }

    protected function updateVariation(int $parentId, int $variationId, array $variation): array
    {
        $data = $this->buildVariationData($variation, true);

        try {
            return $this->woocommerce->updateProductVariation($parentId, $variationId, $data);
        } catch (HttpClientException $e) {
            $this->logService->write("Error updating variation: {$e->getMessage()}");
            return [];
        }
    }

    protected function buildSimpleProductData(array $product): array
    {
        $data = [
            'name' => $product['title'],
            'sku' => $product['parent_sku'],
            'regular_price' => $product['price_including'],
            'weight' => $product['weight'],
            'stock_quantity' => $product['stock'],
            'type' => 'simple',
            'manage_stock' => true,
            'status' => 'draft',
        ];

        $dimensions = [];
        if (!empty($product['length'])) {
            $dimensions['length'] = (string) $product['length'];
        }
        if (!empty($product['breadth'])) {
            $dimensions['width'] = (string) $product['breadth'];
        }
        if (!empty($product['height'])) {
            $dimensions['height'] = (string) $product['height'];
        }

        if (!empty($dimensions)) {
            $data['dimensions'] = $dimensions;
        }

        if (!empty($product['sale_price_including']) && $product['sale_price_including'] !== '0.00') {
            $data['sale_price'] = $product['sale_price_including'];
            $data['date_on_sale_from'] = $product['sale_start_date'];
            $data['date_on_sale_to'] = $product['sale_end_date'];
        }

        if ($this->includeImages) {
            $images = [];
            if (!empty($product['image'])) {
                $image1 = str_replace(' ', '%20', $product['image']);
                $images[] = ['src' => $this->imageSourceUrl . $image1];
            }
            if (!$this->assignSingleImage) {
                foreach (['image_2', 'image_3', 'image_4'] as $key) {
                    if (!empty($product[$key])) {
                        $filename = str_replace(' ', '%20', $product[$key]);
                        $images[] = ['src' => $this->imageSourceUrl . $filename];
                    }
                }
            }
            $data['images'] = $images;
        }

        return $data;
    }

    protected function buildVariableParentData(array $product, array $attributes, ?array $categoryId): array
    {
        $data = [
            'name' => $product['title'],
            'sku' => $product['parent_sku'],
            'regular_price' => $product['price_including'],
            'weight' => $product['weight'],
            'type' => 'variable',
            'manage_stock' => false,
            'status' => 'draft',
            'attributes' => $attributes,
        ];
        $dimensions = [];
        if (!empty($product['length'])) {
            $dimensions['length'] = (string) $product['length'];
        }
        if (!empty($product['breadth'])) {
            $dimensions['width'] = (string) $product['breadth'];
        }
        if (!empty($product['height'])) {
            $dimensions['height'] = (string) $product['height'];
        }

        if (!empty($dimensions)) {
            $data['dimensions'] = $dimensions;
        }

        if (!empty($categoryId) && is_array($categoryId)) {
            $data['categories'] = array_map(fn($id) => ['id' => $id], $categoryId);
        }

        if ($this->assignDefaultVariation) {
            $data['default_attributes'] = [
                ['id' => $this->colorIndex, 'name' => $this->colorName, 'option' => $product['color']],
                ['id' => $this->sizeIndex, 'name' => $this->sizeName, 'option' => $product['size'] === 'ZZZ' ? 'GENERAL' : $product['size']]
            ];
        }

        if ($this->includeImages) {
            $images = [];
            if (!empty($product['image'])) {
                $image1 = str_replace(' ', '%20', $product['image']);
                $images[] = ['src' => $this->imageSourceUrl . $image1];
            }
            if (!$this->assignSingleImage) {
                foreach (['image_2', 'image_3', 'image_4'] as $key) {
                    if (!empty($product[$key])) {
                        $filename = str_replace(' ', '%20', $product[$key]);
                        $images[] = ['src' => $this->imageSourceUrl . $filename];
                    }
                }
            }
            $data['images'] = $images;
        }

        return $data;
    }

    protected function buildVariationData(array $variation, $update = false): array
    {
        $variationData = [
            'sku' => $variation['sku'],
            'regular_price' => $variation['price_including'],
            'stock_quantity' => $variation['stock'],
            'manage_stock' => true,
            'weight' => $variation['weight'],
        ];

        $dimensions = [];
        if (!empty($variation['length'])) {
            $dimensions['length'] = (string) $variation['length'];
        }
        if (!empty($variation['breadth'])) {
            $dimensions['width'] = (string) $variation['breadth'];
        }
        if (!empty($variation['height'])) {
            $dimensions['height'] = (string) $variation['height'];
        }

        if (!empty($dimensions)) {
            $data['dimensions'] = $dimensions;
        }

        if(!$update){
            $variationData['attributes'] = [
                ['id' => $this->colorIndex, 'name' => $this->colorName, 'option' => $variation['color']],
                ['id' => $this->sizeIndex, 'name' => $this->sizeName, 'option' => $variation['size'] === 'ZZZ' ? 'GENERAL' : $variation['size']]
            ];
        }

        if ($variation['sale_price_including'] != '0.00') {
            $variationData['sale_price'] = $variation['sale_price_including'];
            $variationData['date_on_sale_from'] = $variation['sale_start_date'];
            $variationData['date_on_sale_to'] = $variation['sale_end_date'];
        }

        if ($this->includeImages && $variation['image']) {
            $imageName = str_replace(' ', '%20', $variation['image']);
            if (fopen($this->imageSourceUrl . $imageName, 'r')) {
                $variationData['image'] = ['src' => $this->imageSourceUrl . $imageName];
            }
        }

        return $variationData;
    }
}
