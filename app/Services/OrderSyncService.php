<?php

namespace App\Services;

use App\Factories\Touch365ApiFactory;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    public function pushWooOrderToPOS(array $wooOrder, int $installationId): bool
    {
        $factory = new Touch365ApiFactory();

        $payload = $this->transformWooOrderToPosPayload($wooOrder);

        Log::info("POS Sync Order Payload:", (array) $payload);

        $touch365Api = $factory->fromInstallation($installationId);

        $body = ['data' => [$payload]];

        Log::info("Final Order Body:", (array) $body);

        // POST request
        $response = $touch365Api->call('POST', '/api/order', [], $body);

        Log::info('Touch365 API Order Sync Response:', (array) $response);

        if (isset($response['status']) && $response['status'] === 'success') {
            Log::info("✅ POS Order Sync Success: {$payload['ORDERNUMBER']}");
            return true;
        }

        Log::error("❌ POS Order Sync Failed", [
            'order' => $body,
            'response' => $response
        ]);

        return false;
    }

    protected function transformWooOrderToPosPayload(array $order): array
    {
        $billing = $order['billing'] ?? [];
        $shipping = $order['shipping'] ?? [];
        $shippingLine = $order['shipping_lines'][0] ?? [];

        // ---- Line Items Formatting According To Touch365 API ---- //
        $lineItems = [];
        foreach ($order['line_items'] ?? [] as $item) {

            $sizeName = 'Default';
            $colorName = 'Default';

            foreach ($item['meta_data'] ?? [] as $meta) {
                if (isset($meta['key'])) {
                    $k = strtolower($meta['key']);

                    if ($k === 'size' || $k === 'sizename') {
                        $sizeName = $meta['value'] ?? 'Default';
                    }
                    if ($k === 'color' || $k === 'colour' || $k === 'colname') {
                        $colorName = $meta['value'] ?? 'Default';
                    }
                }
            }

            $lineItems[] = [
                "CODE" => $item['sku'] ?? '',
                "DESCRIPTION" => $item['name'] ?? '',
                "QTY" => (int) ($item['quantity'] ?? 1),
                "PRICE" => (float) ($item['price'] ?? 0),
                "SIZECODE" => "XL",  // Optional placeholder (Touch365 example)
                "COLCODE" => "BLUE", // Optional placeholder
                "SIZENAME" => $sizeName,
                "COLNAME" => $colorName,
            ];
        }

        // ---- Final Payload According To Touch365 API Format ---- //
        return [
            "ORDERNUMBER" => $order['id'] ?? '',
            "ORDERDATE" => date('Y-m-d', strtotime($order['date_created'] ?? now())),
            "ORDERTIME" => date('H:i:s', strtotime($order['date_created'] ?? now())),
            "CURRENCY" => $order['currency'] ?? 'ZAR',
            "ORDERTOTAL" => (float) ($order['total'] ?? 0),
            "SUBTOTAL" => 0,
            "DISCOUNTTOTAL" => "0.00",

            "SHIPPINGCOST" => (float) ($order['shipping_total'] ?? 0),
            "PAYMENTMETHOD" => $order['payment_method_title'] ?? $order['payment_method'] ?? '',
            "PAYMENTREF" => $order['transaction_id'] ?? '',
            "TRANSACTIONID" => $order['transaction_id'] ?? '',
            "CUSTOMERNOTE" => $order['customer_note'] ?? '',
            "VATINDICATOR" => $this->getVatIndicator($order),

            // Billing
            "BILLINGEMAIL" => $billing['email'] ?? '',
            "BILLINGPHONE" => $billing['phone'] ?? '',
            "BILLINGFIRSTNAME" => $billing['first_name'] ?? '',
            "BILLINGLASTNAME" => $billing['last_name'] ?? '',
            "BILLINGCOMPANY" => $billing['company'] ?? '',
            "BILLINGADDRESS1" => $billing['address_1'] ?? '',
            "BILLINGADDRESS2" => $billing['address_2'] ?? '',
            "BILLINGADDRESS3" => "",
            "BILLINGADDRESS4" => "",
            "BILLINGCITY" => $billing['city'] ?? '',
            "BILLINGSTATE" => $billing['state'] ?? '',
            "BILLINGPOSTCODE" => $billing['postcode'] ?? '',
            "BILLINGCOUNTRY" => $billing['country'] ?? '',

            // Shipping
            "SHIPPINGFIRSTNAME" => $shipping['first_name'] ?? $billing['first_name'] ?? '',
            "SHIPPINGLASTNAME" => $shipping['last_name'] ?? $billing['last_name'] ?? '',
            "SHIPPINGCOMPANY" => $shipping['company'] ?? $billing['company'] ?? '',
            "SHIPPINGADDRESS1" => $shipping['address_1'] ?? $billing['address_1'] ?? '',
            "SHIPPINGADDRESS2" => $shipping['address_2'] ?? $billing['address_2'] ?? '',
            "SHIPPINGADDRESS3" => "",
            "SHIPPINGADDRESS4" => "",
            "SHIPPINGCITY" => $shipping['city'] ?? $billing['city'] ?? '',
            "SHIPPINGSTATE" => $shipping['state'] ?? $billing['state'] ?? '',
            "SHIPPINGPOSTCODE" => $shipping['postcode'] ?? $billing['postcode'] ?? '',
            "SHIPPINGCOUNTRY" => $shipping['country'] ?? $billing['country'] ?? '',
            "SHIPPINGPHONE" => $shipping['phone'] ?? $billing['phone'] ?? '',
            "SHIPPINGMETHOD" => $shippingLine['method_title'] ?? 'Courier',

            // Order Items
            "ORDERITEMS" => $lineItems,
        ];
    }

    protected function getVatIndicator(array $order): string
    {
        foreach ($order['meta_data'] ?? [] as $meta) {
            if (($meta['key'] ?? '') === 'vat_exempt' && ($meta['value'] ?? '') === 'yes') {
                return 'E'; // Exempt
            }
        }
        return 'I'; // Inclusive
    }
}
