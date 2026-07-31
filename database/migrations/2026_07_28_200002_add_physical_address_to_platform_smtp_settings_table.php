<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_smtp_settings', function (Blueprint $table) {
            $table->text('physical_address')->nullable()->after('from_name');
        });
    }

    public function down(): void
    {
        Schema::table('platform_smtp_settings', function (Blueprint $table) {
            $table->dropColumn('physical_address');
        });
    }
};
