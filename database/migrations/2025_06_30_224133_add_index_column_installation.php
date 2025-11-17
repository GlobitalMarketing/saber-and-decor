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
            $table->string('colorIndex')->nullable()->after('password');
            $table->string('colorName')->nullable()->after('password');
            $table->string('sizeIndex')->nullable()->after('password');
            $table->string('sizeName')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn(['colorIndex', 'colorName', 'sizeIndex', 'sizeName']);
        });
    }
};
