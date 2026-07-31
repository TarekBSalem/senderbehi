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
            Schema::table('campaigns', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->change();
                $table->text('body_html')->nullable()->after('subject');
                $table->boolean('use_custom_smtp')->default(false)->after('scheduled_at');
                $table->string('smtp_host')->nullable()->after('use_custom_smtp');
                $table->integer('smtp_port')->nullable()->after('smtp_host');
                $table->string('smtp_username')->nullable()->after('smtp_port');
                $table->text('smtp_password')->nullable()->after('smtp_username');
                $table->string('smtp_encryption')->nullable()->after('smtp_password');
                $table->string('smtp_from_email')->nullable()->after('smtp_encryption');
                $table->string('smtp_from_name')->nullable()->after('smtp_from_email');
                $table->integer('sends_per_minute')->nullable()->after('smtp_from_name');
            });

            return;
        }

        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::create('campaigns_tmp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained();
            $table->string('name');
            $table->string('subject');
            $table->text('body_html')->nullable();
            $table->enum('status', ['draft', 'queued', 'scheduled', 'sending', 'sent', 'partial', 'failed'])->default('draft');
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('use_custom_smtp')->default(false);
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_from_email')->nullable();
            $table->string('smtp_from_name')->nullable();
            $table->integer('sends_per_minute')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO campaigns_tmp (id, user_id, template_id, name, subject, status, total_recipients, sent_count, failed_count, scheduled_at, sent_at, created_at, updated_at) SELECT id, user_id, template_id, name, subject, status, total_recipients, sent_count, failed_count, scheduled_at, sent_at, created_at, updated_at FROM campaigns');
        DB::statement('DROP TABLE campaigns');
        DB::statement('ALTER TABLE campaigns_tmp RENAME TO campaigns');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable(false)->change();
                $table->dropColumn([
                    'body_html',
                    'use_custom_smtp',
                    'smtp_host',
                    'smtp_port',
                    'smtp_username',
                    'smtp_password',
                    'smtp_encryption',
                    'smtp_from_email',
                    'smtp_from_name',
                    'sends_per_minute',
                ]);
            });

            return;
        }

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

        DB::statement('INSERT INTO campaigns_tmp SELECT id, user_id, template_id, name, subject, status, total_recipients, sent_count, failed_count, scheduled_at, sent_at, created_at, updated_at FROM campaigns');
        DB::statement('DROP TABLE campaigns');
        DB::statement('ALTER TABLE campaigns_tmp RENAME TO campaigns');

        DB::statement('PRAGMA foreign_keys=ON');
    }
};
