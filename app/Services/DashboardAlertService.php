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
        $members = Member::query()
            ->with('membershipPlan')
            ->expired()
            ->orderBy('membership_expires_at')
            ->limit(5)
            ->get();

        $count = Member::query()->expired()->count();

        return [
            'type' => AlertType::MembershipExpired,
            'title' => AlertType::MembershipExpired->label(),
            'message' => $count === 1
                ? '1 membership has expired and needs renewal.'
                : "{$count} memberships have expired and need renewal.",
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
        $members = $this->expiringSoonQuery($days)
            ->with('membershipPlan')
            ->orderBy('membership_expires_at')
            ->limit(5)
            ->get();

        $count = $this->expiringSoonQuery($days)->count();

        return [
            'type' => AlertType::MembershipExpiring,
            'title' => "Membership Expiring in {$days} Days",
            'message' => $count === 1
                ? '1 membership will expire within the next '.$days.' days.'
                : "{$count} memberships will expire within the next {$days} days.",
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
        $products = Product::query()
            ->lowStock()
            ->orderBy('stock')
            ->limit(5)
            ->get();

        $count = Product::query()->lowStock()->count();

        return [
            'type' => AlertType::LowStock,
            'title' => AlertType::LowStock->label(),
            'message' => $count === 1
                ? '1 product is at or below minimum stock.'
                : "{$count} products are at or below minimum stock.",
            'count' => $count,
            'severity' => AlertType::LowStock->severity(),
            'action_url' => route('admin.products.index', [
                'stock' => 'low',
            ]),
            'items' => $products->map(fn (Product $product): array => [
                'name' => $product->name,
                'detail' => $product->stock.' in stock (min '.$product->minimum_stock.')',
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

        $members = Member::query()
            ->birthdayToday()
            ->orderBy('name')
            ->limit(5)
            ->get();

        $count = Member::query()->birthdayToday()->count();

        return [
            'type' => AlertType::Birthday,
            'title' => AlertType::Birthday->label(),
            'message' => $count === 1
                ? '1 member is celebrating a birthday today.'
                : "{$count} members are celebrating birthdays today.",
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
