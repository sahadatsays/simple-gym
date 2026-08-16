<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardAlertService
{
    /**
     * @return Collection<int, array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }>
     */
    public function alerts(bool $includeBirthdays = true): Collection
    {
        $expiringDays = 7;

        return collect([
            $this->buildExpiredMembershipAlert(),
            $this->buildExpiringMembershipAlert($expiringDays),
            $this->buildLowStockAlert(),
        ])
            ->when($includeBirthdays, fn (Collection $alerts) => $alerts->push($this->buildBirthdayAlert()))
            ->filter(fn (array $alert): bool => $alert['count'] > 0)
            ->values();
    }

    /**
     * @return array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }
     */
    public function buildExpiredMembershipAlert(): array
    {
        $query = Member::query()->expired();
        $count = (clone $query)->count();

        $members = (clone $query)
            ->with('membershipPlan')
            ->orderBy('membership_expires_at')
            ->limit(5)
            ->get();

        return [
            'type' => AlertType::MembershipExpired,
            'title' => AlertType::MembershipExpired->label(),
            'message' => $count === 1
                ? __('dashboard.alerts.message_expired_one')
                : __('dashboard.alerts.message_expired_many', ['count' => $count]),
            'count' => $count,
            'severity' => AlertType::MembershipExpired->severity(),
            'action_url' => route('admin.reports.show', [
                'report' => 'expired-members',
            ]),
            'items' => $this->mapMemberItems($members, 'expires'),
        ];
    }

    /**
     * @return array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }
     */
    public function buildExpiringMembershipAlert(int $days = 7): array
    {
        $query = $this->expiringSoonQuery($days);
        $count = (clone $query)->count();

        $members = (clone $query)
            ->with('membershipPlan')
            ->orderBy('membership_expires_at')
            ->limit(5)
            ->get();

        return [
            'type' => AlertType::MembershipExpiring,
            'title' => __('dashboard.alerts.membership_expiring_in_days', ['days' => $days]),
            'message' => $count === 1
                ? __('dashboard.alerts.message_expiring_one', ['days' => $days])
                : __('dashboard.alerts.message_expiring_many', ['count' => $count, 'days' => $days]),
            'count' => $count,
            'severity' => AlertType::MembershipExpiring->severity(),
            'action_url' => route('admin.reports.show', [
                'report' => 'upcoming-expiry',
                'from_date' => now()->toDateString(),
                'to_date' => now()->addDays($days)->toDateString(),
            ]),
            'items' => $this->mapMemberItems($members, 'expires'),
        ];
    }

    /**
     * @return array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }
     */
    public function buildLowStockAlert(): array
    {
        $query = Product::query()->lowStock();
        $count = (clone $query)->count();

        $products = (clone $query)
            ->orderBy('stock')
            ->limit(5)
            ->get();

        return [
            'type' => AlertType::LowStock,
            'title' => AlertType::LowStock->label(),
            'message' => $count === 1
                ? __('dashboard.alerts.message_low_stock_one')
                : __('dashboard.alerts.message_low_stock_many', ['count' => $count]),
            'count' => $count,
            'severity' => AlertType::LowStock->severity(),
            'action_url' => route('admin.products.index', [
                'stock' => 'low',
            ]),
            'items' => $products->map(fn (Product $product): array => [
                'name' => $product->name,
                'detail' => __('dashboard.alerts.stock_detail', [
                    'stock' => $product->stock,
                    'minimum' => $product->minimum_stock,
                ]),
            ])->all(),
        ];
    }

    /**
     * @return array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }
     */
    public function buildBirthdayAlert(): array
    {
        if (! config('gym.notifications.birthdays_enabled', true)) {
            return $this->emptyAlert(AlertType::Birthday);
        }

        $query = Member::query()->birthdayToday();
        $count = (clone $query)->count();

        $members = (clone $query)
            ->orderBy('name')
            ->limit(5)
            ->get();

        return [
            'type' => AlertType::Birthday,
            'title' => AlertType::Birthday->label(),
            'message' => $count === 1
                ? __('dashboard.alerts.message_birthday_one')
                : __('dashboard.alerts.message_birthday_many', ['count' => $count]),
            'count' => $count,
            'severity' => AlertType::Birthday->severity(),
            'action_url' => route('admin.members.index'),
            'items' => $members->map(fn (Member $member): array => [
                'name' => $member->name,
                'detail' => $member->member_code,
            ])->all(),
        ];
    }

    /**
     * @return Builder<Member>
     */
    private function expiringSoonQuery(int $days)
    {
        return Member::query()
            ->where('status', MemberStatus::Active)
            ->whereNotNull('membership_expires_at')
            ->whereDate('membership_expires_at', '>=', today())
            ->whereDate('membership_expires_at', '<=', today()->addDays($days));
    }

    /**
     * @param  Collection<int, Member>  $members
     * @return array<int, array{name: string, detail: ?string}>
     */
    private function mapMemberItems(Collection $members, string $detailType): array
    {
        return $members->map(function (Member $member) use ($detailType): array {
            $detail = match ($detailType) {
                'expires' => $member->membership_expires_at?->format('M j, Y'),
                default => $member->member_code,
            };

            return [
                'name' => $member->name,
                'detail' => $detail,
            ];
        })->all();
    }

    /**
     * @return array{
     *     type: AlertType,
     *     title: string,
     *     message: string,
     *     count: int,
     *     severity: string,
     *     action_url: string,
     *     items: array<int, array{name: string, detail: ?string}>
     * }
     */
    private function emptyAlert(AlertType $type): array
    {
        return [
            'type' => $type,
            'title' => $type->label(),
            'message' => '',
            'count' => 0,
            'severity' => $type->severity(),
            'action_url' => route('admin.dashboard'),
            'items' => [],
        ];
    }
}
