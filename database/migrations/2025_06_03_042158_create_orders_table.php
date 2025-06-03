<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ordernumber')->unique();
            $table->string('billing_email');
            $table->string('billing_phone');
            $table->string('billing_firstname');
            $table->string('billing_lastname');
            $table->string('billing_company')->nullable();
            $table->string('billing_address1');
            $table->string('billing_address2')->nullable();
            $table->string('billing_address3')->nullable();
            $table->string('billing_address4')->nullable();
            $table->string('billing_city');
            $table->string('billing_state');
            $table->string('billing_postcode');
            $table->string('billing_country');
            $table->string('shipping_phone');
            $table->string('shipping_firstname');
            $table->string('shipping_lastname');
            $table->string('shipping_company')->nullable();
            $table->string('shipping_address1');
            $table->string('shipping_address2')->nullable();
            $table->string('shipping_address3')->nullable();
            $table->string('shipping_address4')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_postcode');
            $table->string('shipping_country');
            $table->decimal('shipping_cost', 10, 2);
            $table->string('shipping_method');
            $table->string('payment_method');
            $table->string('payment_ref');
            $table->date('order_date');
            $table->time('order_time');
            $table->text('order_comment')->nullable();
            $table->decimal('order_total', 10, 2);
            $table->string('vatindicator');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
