<?php

namespace App\Services;

use App\Factories\Touch365ApiFactory;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    public function pushWooOrderToPOS(array $wooOrder, int $installationId): bool
    {
        // try {
            $factory = new Touch365ApiFactory();
            $payload = $this->transformWooOrderToPosPayload($wooOrder);
            $touch365Api = $factory->fromInstallation($installationId);

            // Send to POS (POST is better than GET for payload)
            $response = $touch365Api->call('POST', '/api/order', $payload);
            Log::info('Touch365 post order api response:', (array) $response);

            if (isset($response['status']) && $response['status'] === 'success') {
                Log::info("✅ POS Order Sync Success: {$payload['ORDERNUMBER']}");
                return true;
            }

            Log::error("❌ POS Order Sync Failed", [
                'order' => $payload,
                'response' => $response
            ]);
            return false;

        // } catch (\Throwable $e) {
        //     Log::error("🚨 POS Order Sync Exception", [
        //         'message' => $e->getMessage(),
        //         'order_id' => $wooOrder['id'] ?? null
        //     ]);
        //     return false;
        // }
    }

    protected function transformWooOrderToPosPayload(array $order): array
    {
        $billing = $order['billing'] ?? [];
        $shipping = $order['shipping'] ?? [];

        $lineItems = [];
        foreach ($order['line_items'] ?? [] as $item) {
            $lineItems[] = [
                'CODE' => $item['sku'] ?? '',
                'DESCRIPTION' => $item['name'] ?? '',
                'QTY' => $item['quantity'] ?? 1,
                'PRICE' => $item['price'] ?? 0,
                'SUBTOTAL' => $item['subtotal'] ?? 0,
                'TOTAL' => $item['total'] ?? 0,
                'IMAGE' => $item['image']['src'] ?? null,
                'META' => json_encode($item['meta_data'] ?? []),
            ];
        }

        $shippingLine = $order['shipping_lines'][0] ?? [];

        return [
            'ORDERNUMBER' => $order['id'] ?? '',
            'ORDERSTATUS' => $order['status'] ?? '',
            'ORDERDATE' => date('Y-m-d', strtotime($order['date_created'] ?? now())),
            'ORDERTIME' => date('H:i:s', strtotime($order['date_created'] ?? now())),
            'CURRENCY' => $order['currency'] ?? 'ZAR',
            'ORDERTOTAL' => $order['total'] ?? 0,
            'SUBTOTAL' => $order['subtotal'] ?? 0,
            'DISCOUNTTOTAL' => $order['discount_total'] ?? 0,
            'SHIPPINGCOST' => $order['shipping_total'] ?? 0,
            'PAYMENTMETHOD' => $order['payment_method_title'] ?? $order['payment_method'] ?? '',
            'TRANSACTIONID' => $order['transaction_id'] ?? '',
            'CUSTOMERNOTE' => $order['customer_note'] ?? '',
            'VATINDICATOR' => $this->getVatIndicator($order),

            // Billing
            'BILLINGEMAIL' => $billing['email'] ?? '',
            'BILLINGPHONE' => $billing['phone'] ?? '',
            'BILLINGFIRSTNAME' => $billing['first_name'] ?? '',
            'BILLINGLASTNAME' => $billing['last_name'] ?? '',
            'BILLINGCOMPANY' => $billing['company'] ?? '',
            'BILLINGADDRESS1' => $billing['address_1'] ?? '',
            'BILLINGADDRESS2' => $billing['address_2'] ?? '',
            'BILLINGCITY' => $billing['city'] ?? '',
            'BILLINGSTATE' => $billing['state'] ?? '',
            'BILLINGPOSTCODE' => $billing['postcode'] ?? '',
            'BILLINGCOUNTRY' => $billing['country'] ?? '',

            // Shipping
            'SHIPPINGFIRSTNAME' => $shipping['first_name'] ?? '',
            'SHIPPINGLASTNAME' => $shipping['last_name'] ?? '',
            'SHIPPINGCOMPANY' => $shipping['company'] ?? '',
            'SHIPPINGADDRESS1' => $shipping['address_1'] ?? '',
            'SHIPPINGADDRESS2' => $shipping['address_2'] ?? '',
            'SHIPPINGCITY' => $shipping['city'] ?? '',
            'SHIPPINGSTATE' => $shipping['state'] ?? '',
            'SHIPPINGPOSTCODE' => $shipping['postcode'] ?? '',
            'SHIPPINGCOUNTRY' => $shipping['country'] ?? '',
            'SHIPPINGPHONE' => $shipping['phone'] ?? $billing['phone'] ?? '',
            'SHIPPINGMETHOD' => $shippingLine['method_title'] ?? 'Courier',

            // Line items
            'ORDERITEMS' => $lineItems,
        ];
    }

    protected function getVatIndicator(array $order): string
    {
        foreach ($order['meta_data'] ?? [] as $meta) {
            if (($meta['key'] ?? '') === 'vat_exempt' && ($meta['value' ] ?? '') === 'yes') {
                return 'E'; // Exempt
            }
        }
        return 'I'; // Inclusive by default
    }
}
