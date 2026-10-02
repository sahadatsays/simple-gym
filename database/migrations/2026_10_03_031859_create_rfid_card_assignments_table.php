<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_card_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('rfid_card_id')->constrained('rfid_cards')->restrictOnDelete();
            $table->timestamp('issue_date');
            $table->timestamp('return_date')->nullable();
            $table->decimal('card_fee', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->string('status');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('open_card_id')->nullable()->unique()->constrained('rfid_cards')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['rfid_card_id', 'return_date']);
        });

        $now = now();

        DB::table('rfid_cards')
            ->whereNotNull('member_id')
            ->whereIn('status', ['assigned', 'blocked'])
            ->orderBy('id')
            ->each(function (object $card) use ($now): void {
                DB::table('rfid_card_assignments')->insert([
                    'member_id' => $card->member_id,
                    'rfid_card_id' => $card->id,
                    'issue_date' => $card->assigned_at ?? $card->created_at ?? $now,
                    'return_date' => null,
                    'card_fee' => $card->card_fee,
                    'deposit_amount' => $card->deposit_amount,
                    'status' => $card->status,
                    'invoice_id' => null,
                    'open_card_id' => $card->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_card_assignments');
    }
};
