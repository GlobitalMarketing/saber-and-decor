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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->nullable()->constrained('installations')->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->text('web_description')->nullable();
            $table->string('parent_sku')->nullable();
            $table->string('sku')->unique(); // replaces stockcode
            $table->decimal('price_excluding', 10, 2);
            $table->decimal('price_including', 10, 2);
            $table->decimal('sale_price_excluding', 10, 2)->nullable();
            $table->decimal('sale_price_including', 10, 2)->nullable();
            $table->timestamp('sale_start_date')->nullable();
            $table->timestamp('sale_end_date')->nullable();

            $table->string('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->string('sub_category_id')->nullable();
            $table->string('sub_category_name')->nullable();

            $table->string('image')->nullable();
            $table->string('image_2')->nullable();
            $table->string('image_3')->nullable();
            $table->string('image_4')->nullable();

            $table->integer('stock')->default(0);
            $table->integer('length')->nullable();     // mm
            $table->integer('breadth')->nullable();    // mm
            $table->integer('height')->nullable();     // mm
            $table->decimal('weight', 8, 2)->nullable(); // kg

            $table->string('size_code')->nullable();
            $table->string('size')->nullable();
            $table->string('color_code')->nullable();
            $table->string('color')->nullable();

            $table->string('item_status')->nullable();
            $table->timestamp('edited')->nullable();
            $table->timestamp('created')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
