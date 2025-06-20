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
            $table->foreignId('installation_id')->nullable()->constrained('installations')->onDelete('cascade');  // Self-referential foreign key
            $table->string('stockcode')->unique();
            $table->string('barcode')->nullable();
            $table->string('isbn')->nullable();
            $table->text('description1');
            $table->text('description2')->nullable();
            $table->text('webdescription')->nullable();
            $table->string('departmentcode');
            $table->string('subdepartmentcode');
            $table->string('manufacturercode');
            $table->string('suppliercode');
            $table->decimal('sellingexcl', 10, 2);
            $table->decimal('sellingincl', 10, 2);
            $table->integer('availableqty');
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->string('image4')->nullable();
            $table->integer('length')->nullable(); // In mm
            $table->integer('breadth')->nullable(); // In mm
            $table->integer('itemheight')->nullable(); // In mm
            $table->decimal('weight', 8, 2)->nullable(); // In kg
            $table->timestamp('promofromdate')->nullable();
            $table->timestamp('promotodate')->nullable();
            $table->decimal('promosellexcl', 10, 2)->nullable();
            $table->decimal('promosellincl', 10, 2)->nullable();
            $table->string('sizecode')->nullable();
            $table->string('colcode')->nullable();
            $table->string('linkcode')->nullable();
            $table->integer('unitspack')->nullable();
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
