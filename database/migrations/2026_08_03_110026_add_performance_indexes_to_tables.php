<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->index('status');
            $table->index('payment_method');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index('status');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_at_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['status']);
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_read_at_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_active']);
        });
    }
};
