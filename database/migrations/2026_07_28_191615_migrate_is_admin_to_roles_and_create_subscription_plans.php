<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create subscription_plans table
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('stripe_price_id')->nullable();
            $table->integer('price_cents');
            $table->string('currency', 3)->default('usd');
            $table->integer('email_limit')->default(0);
            $table->json('features')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create Sanctum personal_access_tokens table for API
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 3. Seed default roles and assign to existing users
        if (Schema::hasTable('roles') && Schema::hasColumn('users', 'is_admin')) {
            $now = now();

            // Create roles
            $roles = [
                ['name' => 'Super Admin', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Admin', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'User', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'Viewer', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ];

            DB::table('roles')->insert($roles);

            $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'User'])->pluck('id', 'name');

            // Assign roles based on is_admin flag
            DB::table('users')->orderBy('id')->each(function ($user) use ($roleIds) {
                $roleName = $user->is_admin ? 'Super Admin' : 'User';
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleIds[$roleName],
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ]);
            });

            // Drop is_admin column
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('personal_access_tokens');
    }
};
