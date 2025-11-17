<?php

namespace App\Repositories\Products;

use App\Models\Installation;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Jobs\CallTouch365ApiJob;
use Carbon\Carbon;
use App\Repositories\Options\OptionRepositoryInterface;
use App\Repositories\Departments\DepartmentRepositoryInterface;
use App\Repositories\Manufacturers\ManufacturerRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    const MYSQL_MAX_TIMESTAMP = '2038-01-19 03:14:07';

    protected array $sizes = [];
    protected array $colors = [];
    protected array $categories = [];
    protected array $subCategories = [];
    protected array $manufacturers = [];

    public function __construct(
        protected OptionRepositoryInterface $optionRepository,
        protected DepartmentRepositoryInterface $departmentRepository,
        protected ManufacturerRepositoryInterface $manufacturerRepository
    ) {
    }

    public function storeProducts(array $productMasters, int $installation_id): void
    {
        $this->loadAttributeMaps($installation_id);

        foreach ($productMasters as $productMaster) {
            $masterSku = $productMaster['STOCKCODE'] ?? null;
            if (!isset($productMaster['products']) || !is_array($productMaster['products'])) {
                \Log::warning('⚠️ Missing or invalid products array in productsmaster.', [
                    'master_sku' => $masterSku,
                ]);
                continue;
            }

            foreach ($productMaster['products'] as $apiProduct) {
                try {

                    // If marked as deleted, remove this variation

                    if (($apiProduct['SYNCSTATUS'] ?? '') === 'DELETED') {
                        $sku = $this->generateSku(
                            $apiProduct['STOCKCODE'] ?? '',
                            $apiProduct['SIZECODE'] ?? null,
                            $apiProduct['COLCODE'] ?? null
                        );

                        $deleted = Product::where('sku', $sku)
                            ->where('installation_id', $installation_id)
                            ->delete();

                        // \Log::info("🗑️ Deleted product variation", [
                        //     'sku' => $sku,
                        //     'deleted' => $deleted > 0,
                        //     'installation_id' => $installation_id,
                        // ]);

                        // After deleting the variation, re-activate all matching parent SKUs
                        $parentSku = $apiProduct['STOCKCODE'] ?? null;

                        Product::where('parent_sku', $parentSku)
                            ->where('installation_id', $installation_id)
                            ->update(['item_status' => 1, 'edited' => now()]);

                        // \Log::info("🔁 Refreshed item_status = 1 for all matching parent_sku variations", [
                        //     'parent_sku' => $parentSku,
                        //     'installation_id' => $installation_id,
                        // ]);

                        continue;
                    }


                    $productData = $this->mapProductData($apiProduct);
                    $productData['installation_id'] = $installation_id;

                    Product::updateOrCreate(
                        ['sku' => $productData['sku'], 'installation_id' => $installation_id],
                        $productData
                    );

                    // \Log::info('✅ Product stored/updated.', [
                    //     'sku' => $productData['sku'],
                    //     'installation_id' => $installation_id,
                    // ]);

                } catch (\Exception $e) {
                    // \Log::error('❌ Failed to store product', [
                    //     'master_sku' => $masterSku,
                    //     'sku' => $apiProduct['STOCKCODE'] ?? null,
                    //     'error' => $e->getMessage(),
                    //     'trace' => $e->getTraceAsString()
                    // ]);
                    throw $e;
                }
            }
        }
    }

    protected function mapProductData(array $product): array
    {

        $title = $product['DESCRIPTION1'] ?? '';
        $sku = $product['STOCKCODE'] ?? '';
        $webDescription = $product['WEBDESCRIPTION'] ?? '';

        $webDescription = is_array($webDescription) ? '' : trim(htmlspecialchars(str_replace(["*", "<", ">", "-"], '', $webDescription)));

        $sizeCode = $product['SIZECODE'] ?? '';
        $size = $this->sizes[$sizeCode] ?? null;

        $colorCode = $product['COLCODE'] ?? '';
        $color = $this->colors[$colorCode] ?? null;

        $cat = $product['DEPARTMENTCODE'] ?? '';
        $catName = $this->categories[$cat] ?? null;

        $bra = $product['MANUFACTURERCODE'] ?? '';
        $braName = $this->manufacturers[$bra] ?? null;

        $subcat = $product['SUBDEPARTMENTCODE'] ?? '';
        $subcatName = $this->subCategories[$subcat] ?? null;
        $finalSku = $this->generateSku($sku, $sizeCode, $colorCode);
        $mappedData = [
            'title' => trim($title),
            'description' => trim($product['DESCRIPTION2'] ?? ''),
            'web_description' => $webDescription,
            'parent_sku' => trim($sku),
            'sku' => trim($finalSku),
            'price_excluding' => $product['SELLINGEXCL'] ?? '0.00',
            'price_including' => $product['SELLINGINCL'] ?? '0.00',
            'sale_price_excluding' => $product['PROMOSELLEXCL'] ?? '0.00',
            'sale_price_including' => $product['PROMOSELLINCL'] ?? '0.00',
            // 'sale_start_date' => $product['PROMOFROMDATE'] ?? null,
            // 'sale_end_date' => $product['PROMOTODATE'] ?? null,
            'sale_start_date' => $this->parseDateForStorage($product['PROMOFROMDATE'] ?? null),
            'sale_end_date' => $this->parseDateForStorage($product['PROMOTODATE'] ?? null),
            'category_id' => trim($cat),
            'category_name' => trim($catName ?? ''),
            'brand_id' => trim($bra),
            'brand_name' => trim($braName ?? ''),
            'sub_category_id' => trim($subcat),
            'sub_category_name' => trim($subcatName ?? ''),
            'image' => $product['IMAGE1'] ?? null,
            'image_2' => $product['IMAGE2'] ?? null,
            'image_3' => $product['IMAGE3'] ?? null,
            'image_4' => $product['IMAGE4'] ?? null,
            'stock' => (int) round($product['AVAILABLEQTY'] ?? 0),
            'length' => (int) ($product['LENGTH'] ?? 0),
            'breadth' => (int) ($product['BREADTH'] ?? 0),
            'height' => (int) ($product['ITEMHEIGHT'] ?? 0),
            'weight' => (float) ($product['WEIGHT'] ?? 0),
            'size_code' => $sizeCode,
            'size' => $size,
            'color_code' => $colorCode,
            'color' => $color,
            'item_status' => 1,
            'edited' => now(),
        ];
        if ($product['SYNCSTATUS'] === "CREATED") {
            $mappedData['created'] = now();
        }
        return $mappedData;
    }

    protected function loadAttributeMaps(int $installationId): void
    {
        $this->sizes = $this->optionRepository->getSizesByInstallation($installationId);
        $this->colors = $this->optionRepository->getColorsByInstallation($installationId);
        $this->categories = $this->departmentRepository->getDepartmentsByInstallation($installationId);
        $this->subCategories = $this->departmentRepository->getSubDepartmentsByInstallation($installationId);
        $this->manufacturers = $this->manufacturerRepository->getManufacturersByInstallation($installationId);
    }



    protected function parseDateForStorage(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y H:i:s', $dateString);

            // MySQL TIMESTAMP range: 1970-01-01 00:00:01 to 2038-01-19 03:14:07
            $min = Carbon::create(1970, 1, 1, 0, 0, 1);
            $max = Carbon::create(2038, 1, 19, 3, 14, 7);

            if ($date->lessThan($min) || $date->greaterThan($max)) {
            // Log::warning('Date outside MySQL TIMESTAMP range', [
            //         'date' => $dateString,
            //         'parsed' => $date->format('Y-m-d H:i:s')
            //     ]);
                return null;
            }

            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Log::warning('Failed to parse product date', [
            //     'date' => $dateString,
            //     'error' => $e->getMessage(),
            // ]);
            return null;
        }
    }

    public function getProducts(int $perPage = 100, array $filters = []): LengthAwarePaginator
    {
        // 1️⃣ Get installation
        $installation = Installation::where('site_url', $filters['domain'] ?? '')->firstOrFail();

        // 2️⃣ Build query with filters
        $query = Product::query()->where('installation_id', $installation->id);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('sku', 'like', "%{$filters['search']}%")
                    ->orWhere('title', 'like', "%{$filters['search']}%")
                    ->orWhere('barcode', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%")
                    ->orWhere('web_description', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['sub_category'])) {
            $query->where('sub_category_id', $filters['sub_category']);
        }

        // 3️⃣ Get all products and group by parent_sku
        $allProducts = $query->get()->groupBy(function ($product) {
            // If parent_sku is empty, fallback to SKU itself
            return $product->parent_sku ?: $product->sku;
        });

        // 4️⃣ Paginate the grouped results manually
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $perPage;
        $items = $allProducts->slice(($page - 1) * $perPage, $perPage)->all();
        $paginator = new LengthAwarePaginator(
            $items,
            $allProducts->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        return $paginator;
    }


    public function getProductByCode(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    public function updateProductStock(array $stockData): void
    {
        foreach ($stockData as $item) {
            Product::where('sku', $item['STOCKCODE'])
                ->update(['stock' => $item['AVAILABLEQTY']]);
        }
    }

    public function syncProductsWithTouch365(?int $installationId = null): void
    {
        CallTouch365ApiJob::dispatch('/api/product', $installationId)->onQueue('touch365-sync');
    }

    public function formatProductDates(Product $product): array
    {
        $data = $product->toArray();

        if ($product->sale_start_date) {
            $data['sale_start_date'] = Carbon::parse($product->sale_start_date)
                ->format('d/m/Y H:i:s');
        }

        if ($product->sale_end_date) {
            $data['sale_end_date'] = Carbon::parse($product->sale_end_date)
                ->format('d/m/Y H:i:s');
        }

        return $data;
    }
    protected function generateSku(?string $stockCode, ?string $sizeCode, ?string $colorCode): string
    {
        $stockCode = trim($stockCode ?? '');
        $sizeCode = trim((string) $sizeCode);
        $colorCode = trim((string) $colorCode);

        $parts = array_filter([$sizeCode, $colorCode], fn($v) => $v !== '' && $v !== null && $v !== '0');

        if (count($parts) > 0) {
            return $stockCode . '_' . implode('_', $parts);
        }

        return $stockCode;
    }

}
