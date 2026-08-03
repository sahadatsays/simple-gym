<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceDocumentService;
use Database\Seeders\GymSettingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(GymSettingSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'invoiceadmin',
        'is_active' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $this->plan = MembershipPlan::factory()->create();
});

function createPaidInvoice(InvoiceType $type, array $overrides = []): Invoice
{
    $member = Member::factory()->create([
        'membership_plan_id' => test()->plan->id,
    ]);

    $invoice = Invoice::factory()->paid()->create(array_merge([
        'member_id' => $member->id,
        'membership_plan_id' => test()->plan->id,
        'type' => $type,
        'invoice_number' => 'INV-TEST-'.fake()->unique()->numerify('#####'),
    ], $overrides));

    Payment::factory()->membershipFee()->create([
        'member_id' => $member->id,
        'invoice_id' => $invoice->id,
        'amount' => $invoice->total,
        'receipt_number' => 'RCP-TEST-'.fake()->unique()->numerify('#####'),
    ]);

    return $invoice->fresh(['member', 'membershipPlan', 'payment']);
}

it('renders invoice detail page with payment summary and qr code', function () {
    $invoice = createPaidInvoice(InvoiceType::Registration);

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Registration invoice')
        ->assertSee('Print A4')
        ->assertSee('Thermal Receipt')
        ->assertSee('Download PDF');
});

it('renders printable a4 invoice with logo, totals, and outstanding balance', function () {
    $invoice = createPaidInvoice(InvoiceType::Renewal);

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.print', $invoice))
        ->assertSuccessful()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Renewal Invoice')
        ->assertSee('Payment Summary')
        ->assertSee('Outstanding Balance')
        ->assertSee('Scan to verify invoice')
        ->assertSee('<svg', false);
});

it('renders thermal receipt for pos sales', function () {
    $invoice = createPaidInvoice(InvoiceType::PosSale, [
        'line_items' => [
            [
                'description' => 'Protein Shake',
                'amount' => 150,
                'quantity' => 3,
                'unit_price' => 50,
            ],
        ],
        'subtotal' => 150,
        'total' => 150,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.thermal', $invoice))
        ->assertSuccessful()
        ->assertSee('POS Sale')
        ->assertSee('Protein Shake')
        ->assertSee('Balance')
        ->assertSee('<svg', false);
});

it('downloads invoice pdf', function () {
    $invoice = createPaidInvoice(InvoiceType::Registration);

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.pdf', $invoice))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('shows outstanding balance for unpaid invoices', function () {
    $member = Member::factory()->create(['membership_plan_id' => $this->plan->id]);

    $invoice = Invoice::factory()->create([
        'member_id' => $member->id,
        'membership_plan_id' => $this->plan->id,
        'status' => InvoiceStatus::Unpaid,
        'total' => 2500,
    ]);

    expect($invoice->outstandingBalance())->toBe(2500.0);

    $document = app(InvoiceDocumentService::class)->build($invoice);

    expect($document['summary']['outstanding_balance'])->toBe(2500.0);

    $this->actingAs($this->admin)
        ->get(route('admin.invoices.print', $invoice))
        ->assertSuccessful()
        ->assertSee('Outstanding Balance');
});

it('redirects legacy payment receipt route to thermal invoice', function () {
    $invoice = createPaidInvoice(InvoiceType::PosSale);
    $payment = $invoice->payment;

    $this->actingAs($this->admin)
        ->get(route('admin.payments.receipt', ['payment' => $payment, 'pos' => 1]))
        ->assertRedirect(route('admin.invoices.thermal', ['invoice' => $invoice, 'autoprint' => 1]));
});

it('redirects legacy member receipt route to invoice detail', function () {
    $invoice = createPaidInvoice(InvoiceType::Registration);

    $this->actingAs($this->admin)
        ->get(route('admin.members.receipt', [$invoice->member, $invoice]))
        ->assertRedirect(route('admin.invoices.show', $invoice));
});

it('denies invoice access without payments permission', function () {
    $trainer = User::factory()->create(['username' => 'invoiceuser', 'is_active' => true]);
    $trainer->assignRole('trainer');

    $invoice = createPaidInvoice(InvoiceType::Registration);

    $this->actingAs($trainer)
        ->get(route('admin.invoices.show', $invoice))
        ->assertForbidden();
});
