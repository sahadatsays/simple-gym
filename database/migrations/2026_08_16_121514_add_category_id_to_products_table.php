<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        if (Schema::hasColumn('products', 'category')) {
            DB::table('products')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->each(function (string $categoryName): void {
                    $categoryId = DB::table('categories')->where('name', $categoryName)->value('id');

                    if ($categoryId === null) {
                        $categoryId = DB::table('categories')->insertGetId([
                            'name' => $categoryName,
                            'is_active' => true,
                            'sort_order' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('products')
                        ->where('category', $categoryName)
                        ->update(['category_id' => $categoryId]);
                });

            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        DB::table('products')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $product): void {
                $categoryName = DB::table('categories')->where('id', $product->category_id)->value('name');

                DB::table('products')->where('id', $product->id)->update([
                    'category' => $categoryName,
                ]);
            });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->index('category');
        });
    }
};
