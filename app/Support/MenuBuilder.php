<?php

namespace App\Support;

use Illuminate\Support\Collection;

class MenuBuilder
{
    /**
     * @return Collection<int, array{
     *     key: string,
     *     label: string,
     *     icon: string,
     *     items: Collection<int, array{
     *         label: string,
     *         route: string,
     *         permission: string,
     *         match: string,
     *         icon: string,
     *         active: bool
     *     }>,
     *     active: bool,
     *     single: bool
     * }>
     */
    public static function authorizedGroups(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return collect(config('menu.groups', []))
            ->map(function (array $group) use ($user): ?array {
                $items = collect($group['items'] ?? [])
                    ->filter(fn (array $item): bool => $user->can($item['permission']))
                    ->map(fn (array $item): array => [
                        ...$item,
                        'active' => self::matchesRoute($item['match']),
                    ])
                    ->values();

                if ($items->isEmpty()) {
                    return null;
                }

                return [
                    'key' => $group['key'],
                    'label' => __('navigation.groups.'.$group['key']),
                    'icon' => $group['icon'],
                    'items' => $items->map(fn (array $item): array => [
                        ...$item,
                        'label' => __('navigation.items.'.$item['key']),
                    ]),
                    'active' => $items->contains(fn (array $item): bool => $item['active']),
                    'single' => $items->count() === 1,
                ];
            })
            ->filter()
            ->values();
    }

    private static function matchesRoute(string $pattern): bool
    {
        $patterns = explode('|', $pattern);

        foreach ($patterns as $candidate) {
            if (request()->routeIs(trim($candidate))) {
                return true;
            }
        }

        return false;
    }
}
