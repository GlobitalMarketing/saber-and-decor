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
            $table->string('username')->nullable()->after('installed_at');
            $table->string('password')->nullable()->after('username');
            $table->string('tenant')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'tenant']);
        });
    }
};
