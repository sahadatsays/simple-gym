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
            $table->string('barcode')->nullable()->unique()->after('sku');
            $table->string('category')->nullable()->after('name');
            $table->decimal('purchase_price', 12, 2)->default(0)->after('category');
            $table->decimal('selling_price', 12, 2)->nullable()->after('purchase_price');
            $table->unsignedInteger('stock')->default(0)->after('selling_price');
            $table->unsignedInteger('minimum_stock')->default(5)->after('stock');
            $table->string('status')->default('active')->after('minimum_stock');
        });

        DB::table('products')->orderBy('id')->lazyById()->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'selling_price' => $product->price,
                'stock' => $product->stock_quantity,
                'minimum_stock' => $product->low_stock_threshold,
                'status' => $product->is_active ? 'active' : 'inactive',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'stock_quantity']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price', 'stock_quantity', 'low_stock_threshold', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'stock']);
            $table->index('category');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('sku');
            $table->unsignedInteger('stock_quantity')->default(0)->after('price');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_quantity');
            $table->boolean('is_active')->default(true)->after('low_stock_threshold');
        });

        DB::table('products')->orderBy('id')->lazyById()->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'price' => $product->selling_price,
                'stock_quantity' => $product->stock,
                'low_stock_threshold' => $product->minimum_stock,
                'is_active' => $product->status === 'active',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'stock']);
            $table->dropIndex(['category']);
            $table->dropIndex(['barcode']);
            $table->dropColumn([
                'barcode',
                'category',
                'purchase_price',
                'selling_price',
                'stock',
                'minimum_stock',
                'status',
            ]);
        });
    }
};
