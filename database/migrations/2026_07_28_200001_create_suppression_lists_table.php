<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppression_list', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->enum('reason', ['bounce', 'complaint', 'manual']);
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('email');
            $table->unique('email', 'suppression_list_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_list');
    }
};
