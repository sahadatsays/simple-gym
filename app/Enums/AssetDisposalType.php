<?php

namespace App\Enums;

enum AssetDisposalType: string
{
    case Sold = 'sold';
    case Disposed = 'disposed';
    case Lost = 'lost';
    case DamagedBeyondRepair = 'damaged_beyond_repair';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Sold => 'Sold',
            self::Disposed => 'Disposed',
            self::Lost => 'Lost',
            self::DamagedBeyondRepair => 'Damaged Beyond Repair',
        };
    }

    public function requiresSaleAmount(): bool
    {
        return $this === self::Sold;
    }

    public function toAssetStatus(): AssetStatus
    {
        return match ($this) {
            self::Sold => AssetStatus::Sold,
            self::Disposed => AssetStatus::Disposed,
            self::Lost => AssetStatus::Lost,
            self::DamagedBeyondRepair => AssetStatus::Damaged,
        };
    }
}
