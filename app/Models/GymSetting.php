<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'email',
    'phone',
    'address',
    'logo_path',
    'timezone',
    'currency',
    'receipt_footer',
    'membership_reminder_days',
    'default_admission_fee',
    'enabled_payment_methods',
    'opening_time',
    'closing_time',
    'is_open',
    'meta',
])]
class GymSetting extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_time' => 'datetime:H:i:s',
            'closing_time' => 'datetime:H:i:s',
            'is_open' => 'boolean',
            'default_admission_fee' => 'decimal:2',
            'membership_reminder_days' => 'integer',
            'enabled_payment_methods' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null);
    }

    /**
     * @return array<string, string>
     */
    public function paymentMethodOptions(): array
    {
        $enabled = $this->enabledPaymentMethodValues();

        return collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $method): bool => in_array($method->value, $enabled, true))
            ->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function enabledPaymentMethodValues(): array
    {
        $enabled = $this->enabled_payment_methods;

        if (! is_array($enabled) || $enabled === []) {
            return array_column(PaymentMethod::cases(), 'value');
        }

        return array_values($enabled);
    }
}
