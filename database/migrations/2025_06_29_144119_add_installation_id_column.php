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
        Schema::table('manufacturers', function (Blueprint $table) {
            $table->foreignId('installation_id')->after('id')->constrained('installations')->onDelete('cascade');
        });
        Schema::table('options', function (Blueprint $table) {
            $table->foreignId('installation_id')->after('id')->constrained('installations')->onDelete('cascade');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('installation_id')->after('id')->constrained('installations')->onDelete('cascade');
        });
        Schema::table('products_list', function (Blueprint $table) {
            $table->foreignId('installation_id')->after('id')->constrained('installations')->onDelete('cascade');
        });
        Schema::table('images', function (Blueprint $table) {
            $table->foreignId('installation_id')->after('id')->constrained('installations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturers', function (Blueprint $table) {
            $table->dropForeign(['installation_id']);
        });
        Schema::table('optiions', function (Blueprint $table) {
            $table->dropForeign(['installation_id']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['installation_id']);
        });
        Schema::table('products_list', function (Blueprint $table) {
            $table->dropForeign(['installation_id']);
        });
        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign(['installation_id']);
        });
    }
};
