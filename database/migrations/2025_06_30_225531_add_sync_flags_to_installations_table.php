<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->boolean('child_category_indexing')->default(true);
            $table->boolean('reset_entries')->default(true);
            $table->boolean('assign_default_variation')->default(false);
            $table->boolean('include_images')->default(true);
            $table->boolean('assign_single_image')->default(true);
            $table->boolean('update_images')->default(false);
            $table->boolean('update_only')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn([
                'child_category_indexing',
                'reset_entries',
                'assign_default_variation',
                'include_images',
                'assign_single_image',
                'update_images',
                'update_only',
            ]);
        });
    }
};
