<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Support\MemberTransactionSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('summarizes membership and pos totals for a member', function () {
    $member = Member::factory()->create();

    $registrationInvoice = Invoice::factory()->for($member)->create([
        'type' => InvoiceType::Registration,
        'total' => 1500,
    ]);

    Payment::factory()->for($member)->for($registrationInvoice, 'invoice')->create([
        'type' => PaymentType::AdmissionFee,
        'amount' => 500,
    ]);

    Payment::factory()->for($member)->for($registrationInvoice, 'invoice')->create([
        'type' => PaymentType::MembershipFee,
        'amount' => 1000,
    ]);

    $renewalInvoice = Invoice::factory()->for($member)->create([
        'type' => InvoiceType::Renewal,
        'total' => 800,
    ]);

    Payment::factory()->for($member)->for($renewalInvoice, 'invoice')->create([
        'type' => PaymentType::MembershipFee,
        'amount' => 800,
    ]);

    $posInvoice = Invoice::factory()->for($member)->create([
        'type' => InvoiceType::PosSale,
        'total' => 300,
        'status' => InvoiceStatus::Partial,
    ]);

    Payment::factory()->for($member)->for($posInvoice, 'invoice')->posSale()->create([
        'amount' => 200,
    ]);

    $summary = MemberTransactionSummary::forMember($member);

    expect($summary->totalAdmissionFee)->toBe(500.0)
        ->and($summary->totalMembershipFee)->toBe(1000.0)
        ->and($summary->totalRenewalFee)->toBe(800.0)
        ->and($summary->totalPosPaid)->toBe(200.0)
        ->and($summary->totalPaid)->toBe(2500.0)
        ->and($summary->totalDue)->toBe(100.0)
        ->and($summary->paymentCount)->toBe(4)
        ->and($summary->posOrderCount)->toBe(1);
});

it('returns zero totals for a member without transactions', function () {
    $member = Member::factory()->create();

    $summary = MemberTransactionSummary::forMember($member);

    expect($summary->totalPaid)->toBe(0.0)
        ->and($summary->totalDue)->toBe(0.0)
        ->and($summary->paymentCount)->toBe(0);
});
