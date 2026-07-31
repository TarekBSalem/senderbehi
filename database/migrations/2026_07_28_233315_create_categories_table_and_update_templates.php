<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('is_active')->constrained('categories')->nullOnDelete();
        });

        $distinctCategories = DB::table('templates')
            ->distinct()
            ->pluck('category');

        foreach ($distinctCategories as $category) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => $category,
                'slug' => Str::slug($category),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('templates')
                ->where('category', $category)
                ->update(['category_id' => $categoryId]);
        }

        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('category')->after('is_active');
        });

        DB::table('templates')
            ->join('categories', 'templates.category_id', '=', 'categories.id')
            ->update(['templates.category' => 'categories.name']);

        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
