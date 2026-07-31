<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_smtp_settings', function (Blueprint $table) {
            $table->integer('sends_per_minute')->default(60)->after('daily_limit');
            $table->integer('daily_sent_count')->default(0)->after('sends_per_minute');
            $table->date('daily_sent_date')->nullable()->after('daily_sent_count');
        });
    }

    public function down(): void
    {
        Schema::table('platform_smtp_settings', function (Blueprint $table) {
            $table->dropColumn(['sends_per_minute', 'daily_sent_count', 'daily_sent_date']);
        });
    }
};
