<?php

namespace App\Support;

use Illuminate\Support\Collection;

class MenuBuilder
{
    /**
     * @return Collection<int, array{label: string, route: string, permission: string, match: string}>
     */
    public static function authorizedItems(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return collect(config('menu.items', []))
            ->filter(fn (array $item): bool => $user->can($item['permission']));
    }
}
