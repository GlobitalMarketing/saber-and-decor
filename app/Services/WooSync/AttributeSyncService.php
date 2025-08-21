<?php

namespace App\Services\WooSync;

use App\Factories\WooClientFactory;
use App\Services\WooSync\LogService;
use App\Services\ProductExportService;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class AttributeSyncService
{
    protected ProductExportService $productsService;
    protected LogService $logService;
    protected $woocommerce;

    protected string $colorsJsonUrl = '/wp-content/uploads/products_colors.json';
    protected string $sizesJsonUrl = '/wp-content/uploads/products_sizes.json';

    protected int $colorIndex;
    protected string $colorName ;
    protected int $sizeIndex;
    protected string $sizeName;

    public function __construct(ProductExportService $productsService, LogService $logService)
    {
        $this->productsService = $productsService;
        $this->logService = $logService;
    }

    public function syncMissingTerms(int $installationId): array
    {
        $this->woocommerce = WooClientFactory::make($installationId);
        $wc = $this->woocommerce->getCredentials();
        $this->colorsJsonUrl = rtrim($wc->site_url, '/') . $this->colorsJsonUrl;
        $this->sizesJsonUrl = rtrim($wc->site_url, '/') . $this->sizesJsonUrl;

        $this->colorIndex = $wc->colorIndex;
        $this->colorName = $wc->colorName;
        $this->sizeIndex = $wc->sizeIndex;
        $this->sizeName = $wc->sizeName;

        $colors = $this->productsService->fetchTerms('color');
        $sizes = $this->productsService->fetchTerms('size');
        try {
            $siteColors = json_decode(file_get_contents($this->colorsJsonUrl), true);
        } catch (\Throwable $th) {
            $siteColors = [];
        }
        
        try {
            $siteSizes = json_decode(file_get_contents($this->sizesJsonUrl), true);
        } catch (\Throwable $th) {
            $siteSizes = [];
        }
        
        

        $colorsToCreate = array_diff($colors['terms'], $siteColors);
        $sizesToCreate = array_diff($sizes['terms'], $siteSizes);

        $results = ['colors' => [], 'sizes' => []];

        if (!empty($colorsToCreate)) {
            $payload = [
                'create' => array_map(fn($c) => ['name' => $c], $colorsToCreate)
            ];
            try {
                $results['colors'] = $this->woocommerce->batchProductAttributeTerms($this->colorIndex, $payload);
            } catch (HttpClientException $e) {
                $this->logService->write("Error syncing colors: {$e->getMessage()}");
            }
        }

        if (!empty($sizesToCreate)) {
            $payload = [
                'create' => array_map(function ($s) {
                    return ['name' => $s === 'ZZZ' ? 'GENERAL' : $s];
                }, $sizesToCreate)
            ];
            try {
                $results['sizes'] = $this->woocommerce->batchProductAttributeTerms($this->sizeIndex, $payload);
            } catch (HttpClientException $e) {
                $this->logService->write("Error syncing sizes: {$e->getMessage()}");
            }
        }

        return $results;
    }
}
