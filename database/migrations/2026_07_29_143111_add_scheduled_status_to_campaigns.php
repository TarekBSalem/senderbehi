<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'queued', 'scheduled', 'sending', 'sent', 'partial', 'failed') NOT NULL DEFAULT 'draft'");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');

            Schema::create('campaigns_tmp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('template_id')->constrained();
                $table->string('name');
                $table->string('subject');
                $table->enum('status', ['draft', 'queued', 'scheduled', 'sending', 'sent', 'partial', 'failed'])->default('draft');
                $table->integer('total_recipients')->default(0);
                $table->integer('sent_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });

            DB::statement('INSERT INTO campaigns_tmp SELECT * FROM campaigns');
            DB::statement('DROP TABLE campaigns');
            DB::statement('ALTER TABLE campaigns_tmp RENAME TO campaigns');

            DB::statement('PRAGMA foreign_keys=ON');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'queued', 'sending', 'sent', 'partial', 'failed') NOT NULL DEFAULT 'draft'");
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');

            Schema::create('campaigns_tmp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('template_id')->constrained();
                $table->string('name');
                $table->string('subject');
                $table->enum('status', ['draft', 'queued', 'sending', 'sent', 'partial', 'failed'])->default('draft');
                $table->integer('total_recipients')->default(0);
                $table->integer('sent_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });

            DB::statement('INSERT INTO campaigns_tmp SELECT * FROM campaigns');
            DB::statement('DROP TABLE campaigns');
            DB::statement('ALTER TABLE campaigns_tmp RENAME TO campaigns');

            DB::statement('PRAGMA foreign_keys=ON');
        }
    }
};
