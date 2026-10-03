<?php

namespace Database\Factories;

use App\Enums\LockerReservationStatus;
use App\Models\Locker;
use App\Models\LockerReservation;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LockerReservation>
 */
class LockerReservationFactory extends Factory
{
    protected $model = LockerReservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'locker_id' => Locker::factory(),
            'member_id' => Member::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfMonth()->toDateString(),
            'monthly_fee' => 0,
            'status' => LockerReservationStatus::Active,
            'invoice_id' => null,
            'created_by' => null,
        ];
    }
}
