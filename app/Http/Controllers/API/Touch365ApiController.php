<?php
namespace App\Http\Controllers\API;

use App\Repositories\Departments\DepartmentRepository;
use App\Repositories\Departments\DepartmentRepositoryInterface;
use App\Repositories\Products\ProductRepositoryInterface;
use Exception;
use Illuminate\Http\Request;
use App\Services\Touch365Api;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\API\Touch365ApiBaseController;
use App\Http\Controllers\Controller;

class Touch365ApiController extends Controller
{
    protected Touch365Api $touch365Api;
    protected $DepartmentRepository;
    protected $productRepository;

    public function __construct(
        Touch365Api $touch365Api,
        DepartmentRepositoryInterface $departmentRepositoryInterface,
        ProductRepositoryInterface $productRepositoryInterface
    ) {
        $this->touch365Api = $touch365Api;
        $this->DepartmentRepository = $departmentRepositoryInterface;
        $this->productRepository = $productRepositoryInterface;
    }

    public function getDepartment(): JsonResponse
    {
        \Log::info("Fetching Departments");
        $response = $this->DepartmentRepository->departments();
        return response()->json($response);
    }

    public function postDepartment(Request $request): JsonResponse
    {
        $data = $request->all();
        \Log::info("Posting Department", ['data' => $data]);
        $response = $this->touch365Api->call('POST', '/api/department', [], $data);
        return response()->json($response);
    }

    public function getImage(): JsonResponse
    {
        $response = $this->touch365Api->call('GET', '/api/image');
        return response()->json($response);
    }

    public function postImage(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('POST', '/api/image', [], $data);
        return response()->json($response);
    }

    public function deleteImage(Request $request): JsonResponse
    {
        $data = $request->all();
        // Assuming your API uses POST for delete, or adjust method to DELETE if supported
        $response = $this->touch365Api->call('DELETE', '/api/image', [], $data);
        return response()->json($response);
    }

    public function getManufacturer(): JsonResponse
    {
        $response = $this->touch365Api->call('GET', '/api/manufacturer');
        return response()->json($response);
    }

    public function postManufacturer(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('POST', '/api/manufacturer', [], $data);
        return response()->json($response);
    }

    public function getOption(): JsonResponse
    {
        $response = $this->touch365Api->call('GET', '/api/option');
        return response()->json($response);
    }

    public function postOption(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('POST', '/api/option', [], $data);
        return response()->json($response);
    }

    public function getOrder(): JsonResponse
    {
        \Log::info("Fetching Orders");
        $response = $this->touch365Api->call('GET', '/api/order');
        return response()->json($response);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('PUT', '/api/order', [], $data);
        return response()->json($response);
    }

    public function postOrder(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('POST', '/api/order', [], $data);
        return response()->json($response);
    }

    /**
     * Get products from Touch365 API
     */
    public function getProduct(): JsonResponse
    {
        return $response = $this->touch365Api->call('GET', '/api/product');

        if ($response->successful()) {
            $products = $response->json();
            $this->productRepository->storeProducts($products['productsmaster'] ?? [], auth()->id());
            return response()->json($products);
        }

        return response()->json(['error' => 'Failed to fetch products'], $response->status());
    }
    public function updateProduct(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('PUT', '/api/product', [], $data);
        return response()->json($response);
    }

    /**
     * Post products to Touch365 API
     */
    public function postProduct(Request $request)
    {
        $validated = $request->validate([
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

        return $response = $this->touch365Api->call('POST', '/api/product', [], $validated);

        if ($response->successful()) {
            $this->productRepository->storeProducts($validated['productsmaster'], auth()->id());
        }

        return response()->json($response->json(), $response->status());
    }
    public function deleteProduct(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('DELETE', '/api/product', [], $data);
        return response()->json($response);
    }

    public function getProductOption(): JsonResponse
    {
        $response = $this->touch365Api->call('GET', '/api/product/option');
        return response()->json($response);
    }

    public function getProductQuantity(): JsonResponse
    {
        $response = $this->touch365Api->call('GET', '/api/product/qty');
        return response()->json($response);
    }
}
