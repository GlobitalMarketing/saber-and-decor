<?php
namespace App\Http\Controllers\API;

use App\Repositories\Departments\DepartmentRepository;
use App\Repositories\Departments\DepartmentRepositoryInterface;
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

    public function __construct(
        Touch365Api $touch365Api,
        DepartmentRepositoryInterface $departmentRepositoryInterface
    ) {
        $this->touch365Api = $touch365Api;
        $this->DepartmentRepository = $departmentRepositoryInterface;
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

    public function getProduct(): JsonResponse
    {
        $response = $this->touch365Api->call('GET', '/api/product');
        return response()->json($response);
    }

    public function updateProduct(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('PUT', '/api/product', [], $data);
        return response()->json($response);
    }

    public function postProduct(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->touch365Api->call('POST', '/api/product', [], $data);
        return response()->json($response);
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
