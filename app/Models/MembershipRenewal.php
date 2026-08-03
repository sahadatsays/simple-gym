<?php

namespace App\Models;

use Database\Factories\MembershipRenewalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'membership_plan_id',
    'invoice_id',
    'previous_expires_at',
    'new_expires_at',
    'renewed_at',
])]
class MembershipRenewal extends Model
{
    /** @use HasFactory<MembershipRenewalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'previous_expires_at' => 'date',
            'new_expires_at' => 'date',
            'renewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<MembershipPlan, $this>
     */
    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
