<?php

namespace App\Repositories\Products;

use App\Models\Installation;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Jobs\CallTouch365ApiJob;
use Carbon\Carbon;

class ProductRepository implements ProductRepositoryInterface
{
    // MySQL maximum timestamp (2038-01-19 03:14:07)
    const MYSQL_MAX_TIMESTAMP = '2038-01-19 03:14:07';

    /**
     * Store products from Touch365 API response
     */
    public function storeProducts(array $products, ?string $domain = null): void
    {
        $getInstallationId = Installation::where('site_url', $domain)->first();
        foreach ($products as $product) {
            try {
                $productData = $this->mapProductData($product);
                $productData['installation_id'] = $getInstallationId->id;
                Product::updateOrCreate(
                    ['stockcode' => $product['STOCKCODE']],
                    $productData
                );

            } catch (\Exception $e) {
                Log::error('Failed to store product', [
                    'stockcode' => $product['STOCKCODE'] ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // Re-throw to handle in controller
            }
        }
    }

    /**
     * Map API data to database columns with proper date handling
     */
    protected function mapProductData(array $apiProduct): array
    {
        return [
            'stockcode' => $apiProduct['STOCKCODE'],
            'barcode' => $apiProduct['BARCODE'] ?? null,
            'isbn' => $apiProduct['ISBN'] ?? null,
            'description1' => $apiProduct['DESCRIPTION1'],
            'description2' => $apiProduct['DESCRIPTION2'] ?? null,
            'webdescription' => $apiProduct['WEBDESCRIPTION'] ?? null,
            'departmentcode' => $apiProduct['DEPARTMENTCODE'],
            'subdepartmentcode' => $apiProduct['SUBDEPARTMENTCODE'],
            'manufacturercode' => $apiProduct['MANUFACTURERCODE'],
            'suppliercode' => $apiProduct['SUPPLIERCODE'],
            'sellingexcl' => $apiProduct['SELLINGEXCL'],
            'sellingincl' => $apiProduct['SELLINGINCL'],
            'availableqty' => $apiProduct['AVAILABLEQTY'],
            'image1' => $apiProduct['IMAGE1'] ?? null,
            'image2' => $apiProduct['IMAGE2'] ?? null,
            'image3' => $apiProduct['IMAGE3'] ?? null,
            'image4' => $apiProduct['IMAGE4'] ?? null,
            'length' => $apiProduct['LENGTH'] ?? null,
            'breadth' => $apiProduct['BREADTH'] ?? null,
            'itemheight' => $apiProduct['ITEMHEIGHT'] ?? null,
            'weight' => $apiProduct['WEIGHT'] ?? null,
            'promofromdate' => $this->parseDateForStorage($apiProduct['PROMOFROMDATE'] ?? null),
            'promotodate' => $this->parseDateForStorage($apiProduct['PROMOTODATE'] ?? null),
            'promosellexcl' => $apiProduct['PROMOSELLEXCL'] ?? null,
            'promosellincl' => $apiProduct['PROMOSELLINCL'] ?? null,
            'sizecode' => $apiProduct['SIZECODE'] ?? null,
            'colcode' => $apiProduct['COLCODE'] ?? null,
            'linkcode' => $apiProduct['LINKCODE'] ?? null,
            'unitspack' => $apiProduct['UNITSPACK'] ?? null,
        ];
    }

    /**
     * Parse and convert Touch365 date format (d/m/Y H:i:s) to MySQL-compatible format
     */
    protected function parseDateForStorage(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y H:i:s', $dateString);
            $mysqlMax = Carbon::parse(self::MYSQL_MAX_TIMESTAMP);

            // Cap dates at MySQL's maximum supported timestamp
            if ($date->greaterThan($mysqlMax)) {
                Log::warning('Date exceeds MySQL maximum, capping to max value', [
                    'original_date' => $dateString,
                    'capped_date' => self::MYSQL_MAX_TIMESTAMP
                ]);
                return self::MYSQL_MAX_TIMESTAMP;
            }

            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', [
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get paginated products with filters
     */
    public function getProducts(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {

        $getInstallationId = Installation::where('site_url', $filters['domain'])->first();

        $query = Product::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('stockcode', 'like', "%{$filters['search']}%")
                    ->orWhere('description1', 'like', "%{$filters['search']}%")
                    ->orWhere('barcode', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['department'])) {
            $query->where('departmentcode', $filters['department']);
        }

        if (!empty($filters['subdepartment'])) {
            $query->where('subdepartmentcode', $filters['subdepartment']);
        }
        return $query->where('installation_id', $getInstallationId->id)->paginate($perPage);
    }

    /**
     * Get single product by stockcode
     */
    public function getProductByCode(string $stockcode): ?Product
    {
        return Product::where('stockcode', $stockcode)->first();
    }

    /**
     * Update product stock quantities
     */
    public function updateProductStock(array $stockData): void
    {
        foreach ($stockData as $item) {
            Product::where('stockcode', $item['STOCKCODE'])
                ->update(['availableqty' => $item['AVAILABLEQTY']]);
        }
    }

    /**
     * Initiate product sync with Touch365 API
     */
    public function syncProductsWithTouch365(?int $installationId = null): void
    {
        // CallTouch365ApiJob::get('/api/product', [], [], $installationId)
        //     ->onQueue('touch365-sync')
        //     ->dispatch();
    }

    /**
     * Format product dates for API response (convert back to d/m/Y H:i:s)
     */
    public function formatProductDates(Product $product): array
    {
        $data = $product->toArray();

        if ($product->promofromdate) {
            $data['promofromdate'] = Carbon::parse($product->promofromdate)
                ->format('d/m/Y H:i:s');
        }

        if ($product->promotodate) {
            $data['promotodate'] = Carbon::parse($product->promotodate)
                ->format('d/m/Y H:i:s');
        }

        return $data;
    }
}