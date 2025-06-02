<?php

namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use App\Services\Touch365Api;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\API\Touch365ApiBaseController;
use GuzzleHttp\Exception\GuzzleException;

class Touch365ApiController extends Touch365ApiBaseController
{

    /**
     * **************** Departments ***************
     * get the departments
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function getDepartment(): JsonResponse
    {
        $api = '/api/department';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * post the department
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function postDepartment(): JsonResponse
    {
        $api = '/api/department';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }



    /**
     * *************** Images ***************
     * get the image
     * @return JsonResponse
     * @throws Exception
     */
    public function getImage(): JsonResponse
    {
        $api = '/api/image';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * post the image
     * @return JsonResponse
     * @throws Exception
     */
    public function postImage(): JsonResponse
    {
        $api = '/api/image';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }

    /**
     * delete the image
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteImafe(): JsonResponse
    {
        $api = '/api/image';
        $response = $this->fetchData($api);
        return $response;
    }



    /**
     * ************** Manufacturer ***************
     * get the manufacturer
     * @return JsonResponse
     * @throws Exception
     */
    public function getManufacturer(): JsonResponse
    {
        $api = '/api/manufacturer';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * post the manufacturer
     * @return JsonResponse
     * @throws Exception
     */
    public function postManufacturer(): JsonResponse
    {
        $api = '/api/manufacturer';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }


    /**
     * ************* Option ***************
     * get the option
     * @return JsonResponse
     * @throws Exception
     */
    public function getOption(): JsonResponse
    {
        $api = '/api/option';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * post the option
     * @return JsonResponse
     * @throws Exception
     */
    public function postOption(array $data): JsonResponse
    {
        $api = '/api/option';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }




    /**
     * ************ Order ***************
     * get the order
     * @return JsonResponse
     * @throws Exception
     */
    public function getOrder(): JsonResponse
    {
        $api = '/api/order';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * update the order
     * @return JsonResponse
     * @throws Exception
     */
    public function updateOrder(): JsonResponse
    {
        $api = '/api/order';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }

    /**
     * post the order
     * @return JsonResponse
     * @throws Exception
     */
    public function postOrder(): JsonResponse
    {
        $api = '/api/order';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }




    /**
     * *********** Products ***************
     * get the product
     * @return JsonResponse
     * @throws Exception
     */
    public function getProduct(): JsonResponse
    {
        $api = '/api/product';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * update the product
     * @return JsonResponse
     * @throws Exception
     */
    public function updateProduct(): JsonResponse
    {
        $api = '/api/product';
        $queries = [];
        $data = [];
        $response = $this->postData($api, $queries, $data);
        return $response;
    }

    /**
     * save the product
     * @return JsonResponse
     * @throws Exception
     */
    public function postProduct(): JsonResponse
    {
        $api = '/api/product';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * delete the product
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteProduct(): JsonResponse
    {
        $api = '/api/product';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * get the product option
     * @return JsonResponse
     * @throws Exception
     */
    public function getProductOption(): JsonResponse
    {
        $api = '/api/product/option';
        $response = $this->fetchData($api);
        return $response;
    }

    /**
     * get the product quantity
     * @return JsonResponse
     * @throws Exception
     */
    public function
    getProductQuantity(): JsonResponse
    {
        $api = '/api/product/qty';
        $response = $this->fetchData($api);
        return $response;
    }



}
