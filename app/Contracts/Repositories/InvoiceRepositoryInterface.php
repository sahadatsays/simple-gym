<?php

namespace App\Contracts\Repositories;

interface InvoiceRepositoryInterface extends RepositoryInterface
{
    public function nextInvoiceNumber(): string;
}
