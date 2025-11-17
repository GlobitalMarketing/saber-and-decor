<?php

namespace App\Jobs;

use App\Models\Order;
use App\Factories\WooClientFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class InsertPosOrdersToWoo implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }


    public function handle(): void
    {
        $woo = WooClientFactory::make($this->order->installation_id);

        // try {
            $wooPayload = $this->mapToWooOrder($this->order);
            Log::info("🚚 Sending POS Order #{$this->order->ordernumber} to WooCommerce.", [
                'installation_id' => $this->order->installation_id,
                'order_id' => $this->order->id,
                'payload' => $wooPayload,
            ]);


            $response = $woo->createOrder($wooPayload);

            $this->order->update(['is_important' => 1]);

            Log::info("✅ POS Order #{$this->order->ordernumber} synced successfully.", [
                'woo_order_id' => $response['id'] ?? null,
                'woo_response' => $response,
            ]);
        // } catch (\Throwable $e) {
        //     Log::error("❌ Failed to sync POS Order #{$this->order->ordernumber}.", [
        //         'installation_id' => $this->order->installation_id,
        //         'order_id' => $this->order->id,
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString(),
        //     ]);
        // }
    }

    protected function mapToWooOrder(Order $o): array
    {
        return [
            'payment_method'        => $o->payment_method ?? 'manual',
            'payment_method_title'  => ucfirst($o->payment_method ?? 'Manual'),
            'set_paid'              => true,

            'transaction_id'        => $o->payment_ref ?? null,
            'customer_note'         => $o->order_comment ?? '',

            'billing' => [
                'first_name' => $o->billing_firstname,
                'last_name'  => $o->billing_lastname,
                'company'    => $o->billing_company,
                'address_1'  => $o->billing_address1,
                'address_2'  => $o->billing_address2,
                'city'       => $o->billing_city,
                'state'      => $o->billing_state,
                'postcode'   => $o->billing_postcode,
                'country'    => $o->billing_country,
                'email'      => $o->billing_email,
                'phone'      => $o->billing_phone,
            ],

            'shipping' => [
                'first_name' => $o->shipping_firstname,
                'last_name'  => $o->shipping_lastname,
                'company'    => $o->shipping_company,
                'address_1'  => $o->shipping_address1,
                'address_2'  => $o->shipping_address2,
                'city'       => $o->shipping_city,
                'state'      => $o->shipping_state,
                'postcode'   => $o->shipping_postcode,
                'country'    => $o->shipping_country,
            ],

            'line_items' => $o->items->map(function ($item) {
                return [
                    'name'     => $item->description,
                    'quantity' => (int) $item->qty,
                    'price'    => number_format((float) $item->price, 2, '.', ''),
                    'sku'      => $item->code,
                    'meta_data' => [
                        ['key' => 'size_code', 'value' => $item->sizecode],
                        ['key' => 'size_name', 'value' => $item->sizename],
                        ['key' => 'color_code', 'value' => $item->colcode],
                        ['key' => 'color_name', 'value' => $item->colname],
                    ],
                ];
            })->toArray(),

            'shipping_lines' => [
                [
                    'method_title' => $o->shipping_method ?? 'Flat Rate',
                    'method_id'    => 'flat_rate',
                    'total'        => number_format((float) $o->shipping_cost, 2, '.', ''),
                ]
            ],
        ];

    }
}
