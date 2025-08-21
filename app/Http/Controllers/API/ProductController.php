<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use App\Models\Product;
use App\Repositories\Products\ProductRepositoryInterface;
use App\Services\Touch365Api;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected ProductRepositoryInterface $productRepository;
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * Get paginated products
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $filters = $request->only(['search', 'department', 'subdepartment', 'domain']);

        $products = $this->productRepository->getProducts($perPage, $filters);

        return response()->json([
            'productsmaster' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    /**
     * Get single product by stockcode
     */
    public function show(string $stockcode): JsonResponse
    {
        $product = $this->productRepository->getProductByCode($stockcode);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'productsmaster' => [$this->formatProduct($product)]
        ]);
    }

    /**
     * Store/update products from Touch365
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'productsmaster' => 'required|array',
            'productsmaster.*.STOCKCODE' => 'required|string|max:50',
            'productsmaster.*.BARCODE' => 'nullable|string|max:50',
            'productsmaster.*.ISBN' => 'nullable|string|max:50',
            'productsmaster.*.DESCRIPTION1' => 'required|string|max:255',
            'productsmaster.*.DESCRIPTION2' => 'nullable|string|max:255',
            'productsmaster.*.WEBDESCRIPTION' => 'nullable|string|max:500',
            'productsmaster.*.DEPARTMENTCODE' => 'required|string|max:20',
            'productsmaster.*.SUBDEPARTMENTCODE' => 'required|string|max:20',
            'productsmaster.*.MANUFACTURERCODE' => 'required|string|max:20',
            'productsmaster.*.SUPPLIERCODE' => 'required|string|max:50',
            'productsmaster.*.SELLINGEXCL' => 'required|numeric|min:0',
            'productsmaster.*.SELLINGINCL' => 'required|numeric|min:0',
            'productsmaster.*.AVAILABLEQTY' => 'required|integer|min:0',
            'productsmaster.*.IMAGE1' => 'nullable|string|max:255',
            'productsmaster.*.IMAGE2' => 'nullable|string|max:255',
            'productsmaster.*.IMAGE3' => 'nullable|string|max:255',
            'productsmaster.*.IMAGE4' => 'nullable|string|max:255',
            'productsmaster.*.LENGTH' => 'nullable|integer|min:0',
            'productsmaster.*.BREADTH' => 'nullable|integer|min:0',
            'productsmaster.*.ITEMHEIGHT' => 'nullable|integer|min:0',
            'productsmaster.*.WEIGHT' => 'nullable|numeric|min:0',
            'productsmaster.*.PROMOFROMDATE' => [
                'nullable',
                'date_format:d/m/Y H:i:s',
                'before_or_equal:productsmaster.*.PROMOTODATE'
            ],
            'productsmaster.*.PROMOTODATE' => [
                'nullable',
                'date_format:d/m/Y H:i:s',
                'after_or_equal:productsmaster.*.PROMOFROMDATE'
            ],
            'productsmaster.*.PROMOSELLEXCL' => 'nullable|numeric|min:0',
            'productsmaster.*.PROMOSELLINCL' => 'nullable|numeric|min:0',
            'productsmaster.*.SIZECODE' => 'nullable|string|max:20',
            'productsmaster.*.COLCODE' => 'nullable|string|max:20',
            'productsmaster.*.LINKCODE' => 'nullable|string|max:20',
            'productsmaster.*.UNITSPACK' => 'nullable|integer|min:1',
        ], [
            'productsmaster.*.PROMOFROMDATE.date_format' => 'The promo from date must be in format dd/mm/YYYY HH:MM:SS',
            'productsmaster.*.PROMOTODATE.date_format' => 'The promo to date must be in format dd/mm/YYYY HH:MM:SS',
            'productsmaster.*.PROMOTODATE.after_or_equal' => 'The promo to date must be after or equal to promo from date',
        ]);

        try {
            // Fetch credentials from DB based on domain (adjust model/column names as needed)
            $credentials = Installation::where('site_url', $request->domain)->firstOrFail();

            // Create Touch365Api instance with DB-fetched credentials
            $touch365Api = new Touch365Api(
                $credentials->username,
                $credentials->password,
                $credentials->tenant
            );

            $this->productRepository->storeProducts($validated['productsmaster'], $request->domain);
            $response = $touch365Api->call('POST', '/api/product', [], $validated);
            if (!$response) {
                return response()->json([
                    'message' => 'Failed to store products in Touch365',
                    'error' => $response
                ], 500);
            }

            return response()->json([
                'message' => 'Products stored successfully',
                'count' => count($validated['productsmaster'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to store products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product stock quantities
     */
    public function updateStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productsmaster' => 'required|array',
            'productsmaster.*.STOCKCODE' => 'required|string',
            'productsmaster.*.AVAILABLEQTY' => 'required|integer',
        ]);

        $this->productRepository->updateProductStock($validated['productsmaster']);

        return response()->json(['message' => 'Stock quantities updated']);
    }

    /**
     * Format product for API response
     */
    protected function formatProduct(Product $product): array
    {
        return [
            'STOCKCODE' => $product->stockcode,
            'BARCODE' => $product->barcode,
            'ISBN' => $product->isbn,
            'DESCRIPTION1' => $product->description1,
            'DESCRIPTION2' => $product->description2,
            'WEBDESCRIPTION' => $product->webdescription,
            'DEPARTMENTCODE' => $product->departmentcode,
            'SUBDEPARTMENTCODE' => $product->subdepartmentcode,
            'MANUFACTURERCODE' => $product->manufacturercode,
            'SUPPLIERCODE' => $product->suppliercode,
            'SELLINGEXCL' => $product->sellingexcl,
            'SELLINGINCL' => $product->sellingincl,
            'AVAILABLEQTY' => $product->availableqty,
            'IMAGE1' => $product->image1,
            'IMAGE2' => $product->image2,
            'IMAGE3' => $product->image3,
            'IMAGE4' => $product->image4,
            'LENGTH' => $product->length,
            'BREADTH' => $product->breadth,
            'ITEMHEIGHT' => $product->itemheight,
            'WEIGHT' => $product->weight,
            'PROMOFROMDATE' => $product->promofromdate?->format('d/m/Y H:i:s'),
            'PROMOTODATE' => $product->promotodate?->format('d/m/Y H:i:s'),
            'PROMOSELLEXCL' => $product->promosellexcl,
            'PROMOSELLINCL' => $product->promosellincl,
            'SIZECODE' => $product->sizecode,
            'COLCODE' => $product->colcode,
            'LINKCODE' => $product->linkcode,
            'UNITSPACK' => $product->unitspack,
        ];
    }
}