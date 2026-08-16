<?php

namespace App\Exceptions;

use RuntimeException;

class PosOrderDeletionException extends RuntimeException
{
    public static function notPosOrder(): self
    {
        return new self('Only POS orders can be deleted.');
    }

    public static function notSameDay(): self
    {
        return new self('Orders can only be deleted on the same day they were placed.');
    }

    public static function productMissing(string $productName): self
    {
        return new self("Cannot delete this order because {$productName} no longer exists.");
    }
}
