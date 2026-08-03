<?php

namespace App\Data;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;

class MemberRegistrationResult
{
    public function __construct(
        public Member $member,
        public Invoice $invoice,
        public Payment $payment,
    ) {}
}
