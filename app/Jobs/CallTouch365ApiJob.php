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
use Illuminate\Support\Arr;


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
            Log::info("Response received for endpoint: {$this->apiEndpoint}");
            // Log::info("Response received for endpoint: {$this->apiEndpoint}", ['response' => $response]);
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
            Log::error('Invalid department data received.');
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
            Log::error('Invalid manufacturer data received.');
        }
    }

    protected function handleOptions(array $response): void
    {
        $optionRepository = app(\App\Repositories\Options\OptionRepositoryInterface::class);
        if (isset($response['sizemaster'])) {
            $options = $response['sizemaster'];
            $mapOptions = [];
            foreach ($options as $key => $option) {
                $mapOptions[$key]['OPTIONCODE'] = $option['SIZECODE'];
                $mapOptions[$key]['OPTIONNAME'] = $option['SIZENAME'];
            }
            $optionRepository->store($mapOptions, 'size', $this->installation_id);

            Log::info('sizemaster stored successfully.');
        } else {
            Log::error('Invalid sizemaster data received.');
        }

        if (isset($response['colourmaster'])) {
            $options = $response['colourmaster'];
            $mapOptions = [];
            foreach ($options as $key => $option) {
                $mapOptions[$key]['OPTIONCODE'] = $option['COLOURCODE'];
                $mapOptions[$key]['OPTIONNAME'] = $option['COLOURNAME'];
            }
            $optionRepository->store($mapOptions, 'colour', $this->installation_id);

            Log::info('colourmaster stored successfully.');
        } else {
            Log::error('Invalid colourmaster data received.');
        }
    }

    protected function handleOrders(array $response, $api): void
    {
        if (isset($response['data']) && is_array($response['data']) && !empty($response['data'])) {
            $orders = $response['data'];

            foreach ($orders as $order) {
                $this->storeOrder($order, false);
            }

            $orderRepository = app(\App\Repositories\Orders\OrderRepositoryInterface::class);
            $ackPayload = $orderRepository->store($orders, $this->installation_id);

            try {
                $api->call('PUT', '/api/order', [], $ackPayload);
                \Log::info('Acknowledged orders as received');
            } catch (\Throwable $e) {
                \Log::error('Failed to acknowledge orders: ' . $e->getMessage(), [
                    'payload' => $ackPayload,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            Log::info('Orders stored successfully.');
        } else {
            Log::error('Invalid orders data received.');
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
            Log::error('Invalid products data received.');
        }
    }


    protected function storeOrder(array $order, bool $isSuccessful): void
    {
        $savedOrder = Order::create([
            'installation_id' => $this->installation_id,
            'ordernumber' => Arr::get($order, 'ORDERNUMBER'),
            'billing_email' => Arr::get($order, 'BILLINGEMAIL'),
            'billing_phone' => Arr::get($order, 'BILLINGPHONE'),
            'billing_firstname' => Arr::get($order, 'BILLINGFIRSTNAME'),
            'billing_lastname' => Arr::get($order, 'BILLINGLASTNAME'),
            'billing_company' => Arr::get($order, 'BILLINGCOMPANY'),
            'billing_address1' => Arr::get($order, 'BILLINGADDRESS1'),
            'billing_address2' => Arr::get($order, 'BILLINGADDRESS2'),
            'billing_address3' => Arr::get($order, 'BILLINGADDRESS3', ''),
            'billing_address4' => Arr::get($order, 'BILLINGADDRESS4', ''),
            'billing_city' => Arr::get($order, 'BILLINGCITY'),
            'billing_state' => Arr::get($order, 'BILLINGSTATE'),
            'billing_postcode' => Arr::get($order, 'BILLINGPOSTCODE'),
            'billing_country' => Arr::get($order, 'BILLINGCOUNTRY'),

            'shipping_phone' => Arr::get($order, 'SHIPPINGPHONE'),
            'shipping_firstname' => Arr::get($order, 'SHIPPINGFIRSTNAME'),
            'shipping_lastname' => Arr::get($order, 'SHIPPINGLASTNAME'),
            'shipping_company' => Arr::get($order, 'SHIPPINGCOMPANY'),
            'shipping_address1' => Arr::get($order, 'SHIPPINGADDRESS1'),
            'shipping_address2' => Arr::get($order, 'SHIPPINGADDRESS2'),
            'shipping_address3' => Arr::get($order, 'SHIPPINGADDRESS3', ''),
            'shipping_address4' => Arr::get($order, 'SHIPPINGADDRESS4', ''),
            'shipping_city' => Arr::get($order, 'SHIPPINGCITY'),
            'shipping_state' => Arr::get($order, 'SHIPPINGSTATE'),
            'shipping_postcode' => Arr::get($order, 'SHIPPINGPOSTCODE'),
            'shipping_country' => Arr::get($order, 'SHIPPINGCOUNTRY'),

            'shipping_cost' => Arr::get($order, 'SHIPPINGCOST', 0),
            'shipping_method' => Arr::get($order, 'SHIPPINGMETHOD'),
            'payment_method' => Arr::get($order, 'PAYMENTMETHOD'),
            'payment_ref' => Arr::get($order, 'PAYMENTREF'),

            'order_date' => Arr::get($order, 'ORDERDATE'),
            'order_time' => Arr::get($order, 'ORDERTIME'),
            'order_comment' => Arr::get($order, 'ORDERCOMMENT', ''),
            'order_total' => Arr::get($order, 'ORDERTOTAL', 0),
            'vatindicator' => Arr::get($order, 'VATINDICATOR'),
            'is_important' => $isSuccessful ? 1 : 0,
        ]);

        // ✅ Store order items safely
        $items = Arr::get($order, 'ORDERITEMS', []);
        if (!empty($items) && is_array($items)) {
            foreach ($items as $item) {
                $product = Product::where('installation_id', $this->installation_id)
                    ->where('sku', Arr::get($item, 'CODE'))
                    ->where('size_code', Arr::get($item, 'SIZECODE', ''))
                    ->where('color_code', Arr::get($item, 'COLCODE', ''))
                    ->first();

                if (!$product) {
                    Log::warning("❌ Product not found for order " . Arr::get($order, 'ORDERNUMBER') .
                        ", SKU: " . Arr::get($item, 'CODE') .
                        ", Size: " . Arr::get($item, 'SIZECODE') .
                        ", Color: " . Arr::get($item, 'COLCODE'));
                    continue;
                }

                OrderItem::create([
                    'order_id' => $savedOrder->id,
                    'product_id' => $product->id,
                    'code' => Arr::get($item, 'CODE'),
                    'sizecode' => Arr::get($item, 'SIZECODE'),
                    'sizename' => Arr::get($item, 'SIZENAME'),
                    'colcode' => Arr::get($item, 'COLCODE'),
                    'colname' => Arr::get($item, 'COLNAME'),
                    'qty' => Arr::get($item, 'QTY', 0),
                    'price' => Arr::get($item, 'PRICE', 0),
                    'description' => Arr::get($item, 'DESCRIPTION'),
                ]);
            }
        }
    }

}
