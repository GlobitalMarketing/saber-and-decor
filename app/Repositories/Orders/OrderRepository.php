<?php
namespace App\Repositories\Orders;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Store Orders and subOrders in the database.
     *
     * @param array $orders
     * @param int $installationId
     * @return array
     */
    public function store(array $orders, int $installationId): array
    {
        $ackPayload = ['data' => []];

        foreach ($orders as $orderData) {
            if (!isset($orderData['ORDERNUMBER'])) {
                //Log::warning('Skipping order: missing ORDERNUMBER', ['order' => $orderData]);
                continue; // Skip this order
            }

            try {
                $order = Order::updateOrCreate(
                    ['ordernumber' => $orderData['ORDERNUMBER']],
                    $this->mapOrderData($orderData, $installationId)
                );

                // Clear old items and insert new ones
                $order->items()->delete();

                if (!empty($orderData['ORDERITEMS']) && is_array($orderData['ORDERITEMS'])) {
                    foreach ($orderData['ORDERITEMS'] as $item) {
                        try {
                            $order->items()->create($this->mapOrderItemData($item));
                        } catch (\InvalidArgumentException $ex) {
                            Log::error("Skipping invalid order item", [
                                'error' => $ex->getMessage(),
                                'item'  => $item,
                                'order' => $orderData['ORDERNUMBER'],
                            ]);
                        }
                    }
                }

                // Append to acknowledgment payload
                $ackPayload['data'][] = [
                    'ORDERNUMBER' => $orderData['ORDERNUMBER'],
                ];
            } catch (\Exception $e) {
                Log::error("Failed to store order", [
                    'error' => $e->getMessage(),
                    'order' => $orderData
                ]);
            }
        }

        return $ackPayload;
    }

    /**
     * Get paginated Orders with optional eager loaded subOrders.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws \Exception
     */
    public function orders(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Order::with(['installation'])->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch Orders: ' . $e->getMessage(), 0, $e);
        }
    }

    protected function mapOrderData(array $data, int $installationId): array
    {
        $map = [
            'ordernumber'         => 'ORDERNUMBER',
            'billing_email'       => 'BILLINGEMAIL',
            'billing_phone'       => 'BILLINGPHONE',
            'billing_firstname'   => 'BILLINGFIRSTNAME',
            'billing_lastname'    => 'BILLINGLASTNAME',
            'billing_company'     => 'BILLINGCOMPANY',
            'billing_address1'    => 'BILLINGADDRESS1',
            'billing_address2'    => 'BILLINGADDRESS2',
            'billing_address3'    => 'BILLINGADDRESS3',
            'billing_address4'    => 'BILLINGADDRESS4',
            'billing_city'        => 'BILLINGCITY',
            'billing_state'       => 'BILLINGSTATE',
            'billing_postcode'    => 'BILLINGPOSTCODE',
            'billing_country'     => 'BILLINGCOUNTRY',
            'shipping_phone'      => 'SHIPPINGPHONE',
            'shipping_firstname'  => 'SHIPPINGFIRSTNAME',
            'shipping_lastname'   => 'SHIPPINGLASTNAME',
            'shipping_company'    => 'SHIPPINGCOMPANY',
            'shipping_address1'   => 'SHIPPINGADDRESS1',
            'shipping_address2'   => 'SHIPPINGADDRESS2',
            'shipping_address3'   => 'SHIPPINGADDRESS3',
            'shipping_address4'   => 'SHIPPINGADDRESS4',
            'shipping_city'       => 'SHIPPINGCITY',
            'shipping_state'      => 'SHIPPINGSTATE',
            'shipping_postcode'   => 'SHIPPINGPOSTCODE',
            'shipping_country'    => 'SHIPPINGCOUNTRY',
            'shipping_cost'       => 'SHIPPINGCOST',
            'shipping_method'     => 'SHIPPINGMETHOD',
            'payment_method'      => 'PAYMENTMETHOD',
            'payment_ref'         => 'PAYMENTREF',
            'order_date'          => 'ORDERDATE',
            'order_time'          => 'ORDERTIME',
            'order_comment'       => 'ORDERCOMMENT',
            'order_total'         => 'ORDERTOTAL',
            'vatindicator'        => 'VATINDICATOR',
        ];

        $mapped = ['installation_id' => $installationId, 'is_imported' => 0];

        foreach ($map as $localKey => $apiKey) {
            $mapped[$localKey] = $data[$apiKey] ?? null; // Safe access
        }

        return $mapped;
    }

    protected function mapOrderItemData(array $item): array
    {
        $map = [
            'code'        => 'CODE',
            'sizecode'    => 'SIZECODE',
            'sizename'    => 'SIZENAME',
            'colcode'     => 'COLCODE',
            'colname'     => 'COLNAME',
            'qty'         => 'QTY',
            'price'       => 'PRICE',
            'description' => 'DESCRIPTION',
        ];

        $mapped = [];

        foreach ($map as $localKey => $apiKey) {
            $mapped[$localKey] = $item[$apiKey] ?? null; // Safe access
        }

        return $mapped;
    }
}
