<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Payment;
use App\Support\DashboardDateRange;
use App\Support\Money;

class FinancialSummaryService
{
    /**
     * @return array{
     *     membership_payments: float,
     *     pos_sales: float,
     *     revenue: float,
     *     expenses: float,
     *     net_operating_result: float,
     *     owner_investment: float
     * }
     */
    public function forRange(DashboardDateRange $range): array
    {
        $paymentTotals = Payment::query()
            ->where('status', PaymentStatus::Completed)
            ->where('paid_at', '>=', $range->from)
            ->where('paid_at', '<=', $range->to)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type IN (?, ?) THEN amount ELSE 0 END), 0) as membership_payments',
                [PaymentType::AdmissionFee->value, PaymentType::MembershipFee->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as pos_sales',
                [PaymentType::PosSale->value]
            )
            ->first();

        $membershipPayments = (float) ($paymentTotals->membership_payments ?? 0);
        $posSales = (float) ($paymentTotals->pos_sales ?? 0);
        $revenue = $membershipPayments + $posSales;

        $expenses = (float) Expense::query()
            ->paid()
            ->whereDate('expensed_at', '>=', $range->from->toDateString())
            ->whereDate('expensed_at', '<=', $range->to->toDateString())
            ->sum('amount');

        $ownerInvestment = (float) Investment::query()
            ->whereDate('invested_at', '>=', $range->from->toDateString())
            ->whereDate('invested_at', '<=', $range->to->toDateString())
            ->sum('amount');

        return [
            'membership_payments' => Money::round($membershipPayments),
            'pos_sales' => Money::round($posSales),
            'revenue' => Money::round($revenue),
            'expenses' => Money::round($expenses),
            'net_operating_result' => Money::round($revenue - $expenses),
            'owner_investment' => Money::round($ownerInvestment),
        ];
    }
}
