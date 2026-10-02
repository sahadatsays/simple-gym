<?php

use App\Models\Member;
use App\Models\RfidCard;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Member::query()
            ->whereNotNull('rfid_card')
            ->each(function (Member $member): void {
                RfidCard::query()->firstOrCreate(
                    ['card_number' => $member->rfid_card],
                    [
                        'status' => 'active',
                        'member_id' => $member->id,
                        'assigned_at' => $member->joined_at ?? now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        RfidCard::query()->delete();
    }
};
