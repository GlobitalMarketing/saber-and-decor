<?php
namespace App\Repositories\Orders;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Store Orders and subOrders in the database.
     *
     * @param array $Orders
     * @param array $subOrders
     * @return void
     */
    public function store(array $orders, int $installationId)
    {
        $ackPayload = ['data' => []];

        foreach ($orders as $orderData) {
            $order = Order::updateOrCreate(
                ['ordernumber' => $orderData['ORDERNUMBER']],
                $this->mapOrderData($orderData, $installationId)
            );

            $order->items()->delete();

            foreach ($orderData['ORDERITEMS'] as $item) {
                $order->items()->create($this->mapOrderItemData($item));
            }

            // Append to batch acknowledgment payload
            $ackPayload['data'][] = [
                'ORDERNUMBER' => $orderData['ORDERNUMBER'],
            ];
        }
        return $ackPayload;
    }

    /**
     * Get paginated Orders with optional eager loaded subOrders.
     *
     * @param int $perPage
     *
     * @throws \Exception on DB failure
     */
    public function orders(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Order::with(['installation'])->paginate($perPage);
        } catch (\Exception $e) {
            // Log here if you want, or just rethrow
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
            if (!array_key_exists($apiKey, $data)) {
                throw new \InvalidArgumentException("Missing expected key in API response: {$apiKey}");
            }
            $mapped[$localKey] = $data[$apiKey];
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
            if (!array_key_exists($apiKey, $item)) {
                throw new \InvalidArgumentException("Missing expected key in ORDERITEM: {$apiKey}");
            }
            $mapped[$localKey] = $item[$apiKey];
        }

        return $mapped;
    }
}
