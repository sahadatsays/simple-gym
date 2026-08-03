<?php

namespace App\Enums;

enum PaymentType: string
{
    case AdmissionFee = 'admission_fee';
    case MembershipFee = 'membership_fee';
    case PosSale = 'pos_sale';

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
            self::AdmissionFee => 'Admission Fee',
            self::MembershipFee => 'Membership Fee',
            self::PosSale => 'POS Sale',
        };
    }
}
