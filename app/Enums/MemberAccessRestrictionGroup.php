<?php

namespace App\Enums;

enum MemberAccessRestrictionGroup: string
{
    case Male = 'male';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male members',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $group): array => [$group->value => $group->label()])
            ->all();
    }
}
