<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->integer('port');
            $table->string('username');
            $table->string('password'); // Encrypted
            $table->string('encryption')->default('tls');
            $table->string('from_email');
            $table->string('from_name');
            $table->integer('daily_limit')->default(1000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_smtp_settings');
    }
};
