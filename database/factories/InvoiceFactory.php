<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $admissionFee = fake()->randomFloat(2, 0, 500);
        $membershipFee = fake()->randomFloat(2, 500, 5000);
        $total = $admissionFee + $membershipFee;

        return [
            'member_id' => Member::factory(),
            'membership_plan_id' => MembershipPlan::factory(),
            'type' => InvoiceType::Registration,
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'subtotal' => $total,
            'total' => $total,
            'status' => InvoiceStatus::Unpaid,
            'line_items' => [
                ['description' => 'Admission Fee', 'amount' => $admissionFee],
                ['description' => 'Membership Fee', 'amount' => $membershipFee],
            ],
            'issued_at' => now(),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
