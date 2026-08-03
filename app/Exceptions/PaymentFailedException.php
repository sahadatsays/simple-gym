<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public static function insufficientAmount(float $required, float $received): self
    {
        return new self("Payment failed: received {$received}, but {$required} is required.");
    }

    public static function exceedsInvoiceAmount(float $invoiceTotal, float $received): self
    {
        return new self("Payment failed: received {$received}, but invoice total is only {$invoiceTotal}.");
    }

    public static function declined(): self
    {
        return new self('Payment was declined and could not be processed.');
    }

    public static function alreadyPaid(): self
    {
        return new self('This invoice has already been paid.');
    }
}
