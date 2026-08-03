<?php

namespace App\Contracts\Repositories;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    public function nextReceiptNumber(): string;
}
