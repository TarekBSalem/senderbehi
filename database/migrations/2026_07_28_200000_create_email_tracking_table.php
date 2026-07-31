<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            $table->enum('type', ['open', 'click', 'bounce', 'complaint']);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['campaign_id', 'type']);
            $table->index(['recipient_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking');
    }
};
