<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductExportService
{
    public function fetchLatestProducts(int $limit = 100): array
    {
        $products = Product::where('item_status', '1')
            ->orderByDesc('edited')
            ->limit($limit)
            ->get();

        return [
            'product_count' => [$products->count()],
            'products' => $products->groupBy('parent_sku')->toArray(),
        ];
    }

    public function fetchAllProducts(): array
    {
        $products = Product::where('item_status', '1')
            ->orderByDesc('edited')
            ->get();

        return [
            'product_count' => [$products->count()],
            'products' => $products->groupBy('parent_sku')->toArray(),
        ];
    }

    public function fetchDistinctProducts(int $groupLimit = 5): array
    {
        $productArray = collect();
        $productCount = 0;
        $productsFinal = [
            'product_count' => [],
            'products' => []
        ];

        // Step 1: Get grouped records with parent_sku + edited (ordered)
        $rawGrouped = Product::where('item_status', '1')
            ->orderByDesc('edited')
            ->get(['parent_sku', 'edited']);

        // Step 2: Deduplicate by parent_sku, limit to N
        $uniqueParentSkus = [];
        foreach ($rawGrouped as $group) {
            if (!in_array($group->parent_sku, $uniqueParentSkus)) {
                $uniqueParentSkus[] = $group->parent_sku;
            }
            if (count($uniqueParentSkus) >= $groupLimit) {
                break;
            }
        }

        // Step 3: Fetch all matching products for the unique parent_skus
        $allProducts = Product::whereIn('parent_sku', $uniqueParentSkus)
            ->orderByDesc('edited')
            ->get();

        $productCount = $allProducts->count();

        // Step 4: Group by parent_sku
        $grouped = $allProducts->groupBy('parent_sku')->toArray();

        $productsFinal['product_count'][] = $productCount;
        $productsFinal['products'] = $grouped;

        return $productsFinal;
    }


    public function fetchTerms(string $termType): array
    {
        $terms = Product::distinct()->pluck($termType)->filter();

        return [
            'term_count' => [$terms->count()],
            'terms' => $terms->values()->toArray(),
        ];
    }

    public function resetProduct(string $sku): bool
    {
        return Product::where('sku', $sku)->update(['item_status' => 0]);
    }

    public function fetchByParentSku(string $sku): array
    {
        $products = Product::where('parent_sku', $sku)
            ->orderByDesc('edited')
            ->limit(100)
            ->get();

        return [
            'product_count' => [$products->count()],
            'products' => $products->groupBy('parent_sku')->toArray(),
        ];
    }

    public function fetchLatestForFeed(int $limit = 200): array
    {
        $products = Product::where('item_status', '1')
            ->select([
                'id', 'sku', 'parent_sku', 'title', 'description', 'web_description',
                'price_including', 'sale_price_including', 'sale_start_date', 'sale_end_date',
                'category_name', 'sub_category_name', 'image', 'image_2', 'image_3', 'image_4',
                'stock', 'length', 'breadth', 'height', 'weight', 'size', 'color', 'item_status'
            ])
            ->limit($limit)
            ->get()
            ->groupBy('parent_sku');

        $result = [
            'product_count' => [$products->flatten()->count()],
            'products' => [],
        ];

        foreach ($products as $sku => $grouped) {
            $base = $grouped->first();
            if ($grouped->count() > 1) {
                $result['products'][$sku] = [
                    'id' => $base->id,
                    'sku' => $sku,
                    'title' => $base->title,
                    'description' => $base->description,
                    'web_description' => $base->web_description,
                    'variations' => $grouped->toArray(),
                ];
            } else {
                $result['products'][$sku] = array_merge(
                    $base->only([
                        'id', 'sku', 'title', 'description', 'web_description',
                        'price_including', 'sale_price_including', 'sale_start_date', 'sale_end_date',
                        'category_name', 'sub_category_name', 'image', 'image_2', 'image_3', 'image_4',
                        'stock', 'length', 'breadth', 'height', 'weight', 'size', 'color'
                    ]),
                    ['sku' => $sku]
                );
            }
        }

        return $result;
    }

    public function createCsvExport(string $filename = 'products.csv'): void
    {
        $header = ["Title", "Sku", "Parent Sku", "Price", "Sale Price", "Color", "Size", "Description", "Web Description", "Category", "Sub Category", "image", "image_2", "image_3", "image_4", "Stock", "Length", "breadth", "height", "weight"];
        $rows = [$header];

        $products = Product::where('item_status', '1')->get();

        foreach ($products as $product) {
            $rows[] = [
                $product->title,
                $product->sku,
                $product->parent_sku,
                $product->price_including ?? '',
                $product->sale_price_including ?? '',
                $product->color,
                $product->size,
                $product->description,
                $product->web_description,
                $product->category_name,
                $product->sub_category_name,
                $product->image,
                $product->image_2,
                $product->image_3,
                $product->image_4,
                $product->stock,
                $product->length,
                $product->breadth,
                $product->height,
                $product->weight
            ];
        }

        $path = storage_path("app/exports/{$filename}");
        $handle = fopen($path, 'w');

        foreach ($rows as $line) {
            fputcsv($handle, $line);
        }

        fclose($handle);
    }
}
