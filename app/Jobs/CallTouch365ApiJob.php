<?php

namespace App\Jobs;

use Exception;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Services\Touch365Api;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Factories\Touch365ApiFactory;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CallTouch365ApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $apiEndpoint;
    protected int $installation_id;
    protected array $requestData;

    public function __construct(string $apiEndpoint, int $installation_id, array $requestData = [])
    {
        $this->apiEndpoint = $apiEndpoint;
        $this->installation_id = $installation_id;
        $this->requestData = $requestData;
    }

    public function handle(Touch365ApiFactory $factory): void
    {
        Log::info("CallTouch365ApiJob started for endpoint: {$this->apiEndpoint}");

        try {
            $touch365Api = $factory->fromInstallation($this->installation_id);
            $response = $touch365Api->call('GET', $this->apiEndpoint, $this->requestData);
            Log::info("Response received for endpoint: {$this->apiEndpoint}", ['response' => $response]);
            match ($this->apiEndpoint) {
                '/api/department' => $this->handleDepartments($response),
                '/api/manufacturer' => $this->handleManufacturers($response),
                '/api/option' => $this->handleOptions($response),
                '/api/order' => $this->handleOrders($response, $touch365Api),
                '/api/product/option' => $this->handleProducts($response),
                default => Log::warning("No handler defined for endpoint: {$this->apiEndpoint}"),
            };
        } catch (Exception $e) {
            Log::error("CallTouch365ApiJob failed for endpoint {$this->apiEndpoint}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function handleDepartments(array $response): void
    {
        if (
            isset($response['stockdepartmentmaster']) &&
            isset($response['stocksubdepartmentmaster'])
        ) {
            $departments = $response['stockdepartmentmaster'];
            $subdepartments = $response['stocksubdepartmentmaster'];

            $departmentRepository = app(\App\Repositories\Departments\DepartmentRepositoryInterface::class);
            $departmentRepository->store($departments, $subdepartments, $this->installation_id);

            Log::info('Departments and subdepartments stored successfully.');
        } else {
            Log::error('Invalid department data received.', ['response' => $response]);
        }
    }

    protected function handleManufacturers(array $response): void
    {
        if (isset($response['stockmanufacturermaster'])) {
            $manufacturers = $response['stockmanufacturermaster'];

            $manufacturerRepository = app(\App\Repositories\Manufacturers\ManufacturerRepositoryInterface::class);
            $manufacturerRepository->store($manufacturers, $this->installation_id);

            Log::info('Manufacturers stored successfully.');
        } else {
            Log::error('Invalid manufacturer data received.', ['response' => $response]);
        }
    }

    protected function handleOptions(array $response): void
    {
        $optionRepository = app(\App\Repositories\Options\OptionRepositoryInterface::class);
        if (isset($response['sizemaster'])) {
            $options = $response['sizemaster'];
            $mapOptions = [];
            foreach($options as $key => $option) {
                $mapOptions[$key]['OPTIONCODE'] = $option['SIZECODE'];
                $mapOptions[$key]['OPTIONNAME'] = $option['SIZENAME'];
            }
            $optionRepository->store($mapOptions, 'size', $this->installation_id);

            Log::info('sizemaster stored successfully.');
        } else {
            Log::error('Invalid sizemaster data received.', ['response' => $response]);
        }

        if (isset($response['colourmaster'])) {
            $options = $response['colourmaster'];
            $mapOptions = [];
            foreach($options as $key => $option) {
                $mapOptions[$key]['OPTIONCODE'] = $option['COLOURCODE'];
                $mapOptions[$key]['OPTIONNAME'] = $option['COLOURNAME'];
            }
            $optionRepository->store($mapOptions, 'colour', $this->installation_id);

            Log::info('colourmaster stored successfully.');
        } else {
            Log::error('Invalid colourmaster data received.', ['response' => $response]);
        }
    }

    protected function handleOrders(array $response, $api): void
    {
        if (isset($response['data'])) {
            $orders = $response['data'];

            $this->storeOrder($orders, false);

            $orderRepository = app(\App\Repositories\Orders\OrderRepositoryInterface::class);
            $ackPayload = $orderRepository->store($orders, $this->installation_id);
            
            try {
                $api->call('PUT', '/api/order', [], $ackPayload);
                \Log::info('Acknowledged orders as received', $ackPayload);
            } catch (\Throwable $e) {
                \Log::error('Failed to acknowledge orders: ' . $e->getMessage(), [
                    'payload' => $ackPayload,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            Log::info('Orders stored successfully.');
        } else {
            Log::error('Invalid orders data received.', ['response' => $response]);
        }
    }

    protected function handleProducts(array $response): void
    {
        if (isset($response['productsmaster'])) {
            $prodcuts = $response['productsmaster'];
            
            $productsRepository = app(\App\Repositories\Products\ProductRepositoryInterface::class);
            $productsRepository->storeProducts($prodcuts, $this->installation_id);

            Log::info('Products stored successfully.');
        } else {
            Log::error('Invalid products data received.', ['response' => $response]);
        }
    }

    protected function storeOrder(array $order, bool $isSuccessful): void
    {
        $savedOrder = Order::create([
            'installation_id'   => $this->installation_id,
            'ordernumber'       => $order['ORDERNUMBER'],
            'billing_email'     => $order['BILLINGEMAIL'],
            'billing_phone'     => $order['BILLINGPHONE'],
            'billing_firstname' => $order['BILLINGFIRSTNAME'],
            'billing_lastname'  => $order['BILLINGLASTNAME'],
            'billing_company'   => $order['BILLINGCOMPANY'],
            'billing_address1'  => $order['BILLINGADDRESS1'],
            'billing_address2'  => $order['BILLINGADDRESS2'],
            'billing_address3'  => $order['BILLINGADDRESS3'] ?? '',
            'billing_address4'  => $order['BILLINGADDRESS4'] ?? '',
            'billing_city'      => $order['BILLINGCITY'],
            'billing_state'     => $order['BILLINGSTATE'],
            'billing_postcode'  => $order['BILLINGPOSTCODE'],
            'billing_country'   => $order['BILLINGCOUNTRY'],
            'shipping_phone'    => $order['SHIPPINGPHONE'],
            'shipping_firstname'=> $order['SHIPPINGFIRSTNAME'],
            'shipping_lastname' => $order['SHIPPINGLASTNAME'],
            'shipping_company'  => $order['SHIPPINGCOMPANY'],
            'shipping_address1' => $order['SHIPPINGADDRESS1'],
            'shipping_address2' => $order['SHIPPINGADDRESS2'],
            'shipping_address3' => $order['SHIPPINGADDRESS3'] ?? '',
            'shipping_address4' => $order['SHIPPINGADDRESS4'] ?? '',
            'shipping_city'     => $order['SHIPPINGCITY'],
            'shipping_state'    => $order['SHIPPINGSTATE'],
            'shipping_postcode' => $order['SHIPPINGPOSTCODE'],
            'shipping_country'  => $order['SHIPPINGCOUNTRY'],
            'shipping_cost'     => $order['SHIPPINGCOST'],
            'shipping_method'   => $order['SHIPPINGMETHOD'],
            'payment_method'    => $order['PAYMENTMETHOD'],
            'payment_ref'       => $order['PAYMENTREF'],
            'order_date'        => $order['ORDERDATE'],
            'order_time'        => $order['ORDERTIME'],
            'order_comment'     => $order['ORDERCOMMENT'] ?? '',
            'order_total'       => $order['ORDERTOTAL'],
            'vatindicator'      => $order['VATINDICATOR'],
            'is_important'      => $isSuccessful ? 1 : 0,
        ]);

        // Store order items
        if (!empty($order['ORDERITEMS']) && is_array($order['ORDERITEMS'])) {
            foreach ($order['ORDERITEMS'] as $item) {
                $product = Product::where('installation_id', $this->installation_id)
                    ->where('sku', $item['CODE'])
                    ->where('size_code', $item['SIZECODE'] ?? '')
                    ->where('color_code', $item['COLCODE'] ?? '')
                    ->first();

                if (!$product) {
                    Log::warning("❌ Product not found for order {$order['ORDERNUMBER']}, SKU: {$item['CODE']}, Size: {$item['SIZECODE']}, Color: {$item['COLCODE']}");
                    continue; // skip this item
                }

                OrderItem::create([
                    'order_id'    => $savedOrder->id,
                    'product_id'  => $product->id,
                    'code'        => $item['CODE'] ?? null,
                    'sizecode'    => $item['SIZECODE'] ?? null,
                    'sizename'    => $item['SIZENAME'] ?? null,
                    'colcode'     => $item['COLCODE'] ?? null,
                    'colname'     => $item['COLNAME'] ?? null,
                    'qty'         => $item['QTY'] ?? 0,
                    'price'       => $item['PRICE'] ?? 0,
                    'description' => $item['DESCRIPTION'] ?? null,
                ]);
            }
        }
    }
}
