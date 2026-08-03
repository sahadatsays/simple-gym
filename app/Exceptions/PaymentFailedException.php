<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public static function insufficientAmount(float $required, float $received): self
    {
        return new self("Payment failed: received {$received}, but {$required} is required.");
    }

    public static function declined(): self
    {
        return new self('Payment was declined and could not be processed.');
    }
}
