<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use App\Services\Touch365Api;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\GuzzleException;

class Touch365ApiBaseController extends Controller
{
    protected Touch365Api $touch365;

    public function __construct(Touch365Api $touch365_service)
    {
        $this->touch365 = $touch365_service;
    }

    /**
     * Fetch data from the Touch365 API.
     *
     * @param string $endpoint
     * @param array $queries
     * @return JsonResponse
     * @throws Exception
     */
    public function fetchData(string $endpoint, array $queries = []): JsonResponse
    {
        if (empty($endpoint)) {
            return response()->json(['error' => 'Endpoint not found'], 404);
        }

        try {
            $getData = $this->touch365->get($endpoint, $queries);
            return response()->json(json_decode($getData));

        } catch (GuzzleException $e) {
            return response()->json(['error' => 'API request failed', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Post data to the Touch365 API.
     *
     * @param string $endpoint
     * @param Request $request
     * @param array $queries
     * @return JsonResponse
     * @throws Exception
     */
    public function postData(string $endpoint, array $queries = [], array $data = []): JsonResponse
    {
        if (empty($endpoint)) {
            return response()->json(['error' => 'Endpoint not found'], 404);
        }

        try {
            $postResponse = $this->touch365->post($endpoint, $queries, $data);
            return response()->json(json_decode($postResponse));

        } catch (GuzzleException $e) {
            return response()->json(['error' => 'API request failed', 'message' => $e->getMessage()], 500);
        }
    }



}
