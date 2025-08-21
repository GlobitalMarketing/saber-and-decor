<?php
namespace App\Services;

use App\Factories\WooClientFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Factories\Touch365ApiFactory;

class OrderSyncService
{
    protected $woocommerce;
    public function pushWooOrderToPOS(array $wooOrder, int $installationId): bool
    {
        $factory = new Touch365ApiFactory();
        
        $payload = $this->transformWooOrderToPosPayload($wooOrder);

        $touch365Api = $factory->fromInstallation($installationId);
        $response = $touch365Api->call('GET', '/api/order', $payload);

        if ($response['status'] === 'success') {
            Log::info("POS Order Sync Success: {$payload['ORDERNUMBER']}");
            return true;
        }

        Log::error("POS Order Sync Failed", ['order' => $payload, 'response' => $response]);
        return false;
    }

    protected function transformWooOrderToPosPayload(array $order): array
    {
        $billing = $order['billing'];
        $shipping = $order['shipping'];

        return [
            'ORDERNUMBER'     => $order['id'],
            'BILLINGEMAIL'    => $billing['email'] ?? '',
            'BILLINGPHONE'    => $billing['phone'] ?? '',
            'BILLINGFIRSTNAME'=> $billing['first_name'] ?? '',
            'BILLINGLASTNAME' => $billing['last_name'] ?? '',
            'BILLINGCOMPANY'  => $billing['company'] ?? '',
            'BILLINGADDRESS1' => $billing['address_1'] ?? '',
            'BILLINGADDRESS2' => $billing['address_2'] ?? '',
            'BILLINGADDRESS3' => '',
            'BILLINGADDRESS4' => '',
            'BILLINGCITY'     => $billing['city'] ?? '',
            'BILLINGSTATE'    => $billing['state'] ?? '',
            'BILLINGPOSTCODE' => $billing['postcode'] ?? '',
            'BILLINGCOUNTRY'  => $billing['country'] ?? '',

            'SHIPPINGPHONE'   => $shipping['phone'] ?? '',
            'SHIPPINGFIRSTNAME'=> $shipping['first_name'] ?? '',
            'SHIPPINGLASTNAME' => $shipping['last_name'] ?? '',
            'SHIPPINGCOMPANY' => $shipping['company'] ?? '',
            'SHIPPINGADDRESS1'=> $shipping['address_1'] ?? '',
            'SHIPPINGADDRESS2'=> $shipping['address_2'] ?? '',
            'SHIPPINGADDRESS3'=> '',
            'SHIPPINGADDRESS4'=> '',
            'SHIPPINGCITY'    => $shipping['city'] ?? '',
            'SHIPPINGSTATE'   => $shipping['state'] ?? '',
            'SHIPPINGPOSTCODE'=> $shipping['postcode'] ?? '',
            'SHIPPINGCOUNTRY' => $shipping['country'] ?? '',

            'SHIPPINGCOST'    => $order['shipping_total'] ?? 0,
            'SHIPPINGMETHOD'  => $order['shipping_lines'][0]['method_title'] ?? 'Flat Rate',
            'PAYMENTMETHOD'   => $order['payment_method'] ?? '',
            'PAYMENTREF'      => $order['transaction_id'] ?? '',
            'ORDERDATE'       => date('Y-m-d', strtotime($order['date_created'])),
            'ORDERTIME'       => date('H:i:s', strtotime($order['date_created'])),
            'ORDERCOMMENT'    => $order['customer_note'] ?? '',
            'ORDERTOTAL'      => $order['total'] ?? 0,
            'VATINDICATOR'    => 'I',

            'ORDERITEMS' => array_map(function ($item) {
                return [
                    'CODE'       => $item['sku'] ?? '',
                    'SIZECODE'   => $item['size'] ?? '',
                    'SIZENAME'   => $item['size_name'] ?? '',
                    'COLCODE'    => $item['color'] ?? '',
                    'COLNAME'    => $item['color_name'] ?? '',
                    'QTY'        => $item['quantity'] ?? 1,
                    'PRICE'      => $item['price'] ?? 0,
                    'DESCRIPTION'=> $item['name'] ?? '',
                ];
            }, $order['line_items']),
        ];
    }
}
