<?php

use App\Http\Controllers\Admin\AttendanceLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GymSettingController;
use App\Http\Controllers\Admin\InvestmentCategoryController;
use App\Http\Controllers\Admin\InvestmentController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberRegistrationController;
use App\Http\Controllers\Admin\MemberRenewalController;
use App\Http\Controllers\Admin\MembershipPlanController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\PosOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RfidCardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ZktecoDeviceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ZktecoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::get('locale/{locale}', LocaleController::class)->name('locale.switch');

Route::prefix('iclock')->group(function (): void {
    Route::match(['get', 'post'], 'cdata', [ZktecoController::class, 'cdata']);
    Route::match(['get', 'post'], 'devicecmd', [ZktecoController::class, 'deviceCmd']);
    Route::match(['get', 'post'], 'getrequest', [ZktecoController::class, 'getRequest']);
    Route::match(['get', 'post'], 'push', [ZktecoController::class, 'push']);
    Route::match(['get', 'post'], 'registry', [ZktecoController::class, 'registry']);
});

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    if (app()->isLocal()) {
        Route::post('login/dev', [LoginController::class, 'devLogin'])->name('login.dev');
    }

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);
        Route::resource('membership-plans', MembershipPlanController::class)->except(['show']);
        Route::get('members/register/create', [MemberRegistrationController::class, 'create'])->name('members.register.create');
        Route::post('members/register', [MemberRegistrationController::class, 'store'])->name('members.register.store');
        Route::get('members/renew/create', [MemberRenewalController::class, 'create'])->name('members.renew.create');
        Route::get('members/{member}/renew', [MemberRenewalController::class, 'edit'])->name('members.renew.edit');
        Route::post('members/{member}/renew', [MemberRenewalController::class, 'store'])->name('members.renew.store');
        Route::get('members/{member}/receipt/{invoice}', [MemberRegistrationController::class, 'receipt'])->name('members.receipt');
        Route::resource('members', MemberController::class);
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::resource('payments', PaymentController::class)->only(['index', 'show']);
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/thermal', [InvoiceController::class, 'thermal'])->name('invoices.thermal');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::resource('investment-categories', InvestmentCategoryController::class)->except(['show']);
        Route::resource('investments', InvestmentController::class);
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('pos/products/search', [PosController::class, 'search'])->name('pos.products.search');
        Route::post('pos/products/scan', [PosController::class, 'scan'])->name('pos.products.scan');
        Route::get('orders', [PosOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{invoice}', [PosOrderController::class, 'show'])->name('orders.show');
        Route::delete('orders/{invoice}', [PosOrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('orders/{invoice}/payments', [PosOrderController::class, 'storePayment'])->name('orders.payments.store');
        Route::get('products/lookup', [ProductController::class, 'lookup'])->name('products.lookup');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::patch('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::post('rfid-cards', [RfidCardController::class, 'store'])->name('rfid-cards.store');
        Route::get('rfid-cards', [RfidCardController::class, 'index'])->name('rfid-cards.index');
        Route::post('rfid-cards/replace', [RfidCardController::class, 'replace'])->name('rfid-cards.replace');
        Route::post('rfid-cards/{rfid_card}/assign', [RfidCardController::class, 'assign'])->name('rfid-cards.assign');
        Route::patch('rfid-cards/{rfid_card}/disable', [RfidCardController::class, 'disable'])->name('rfid-cards.disable');
        Route::patch('rfid-cards/{rfid_card}/enable', [RfidCardController::class, 'enable'])->name('rfid-cards.enable');

        Route::get('settings', [GymSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [GymSettingController::class, 'update'])->name('settings.update');

        Route::prefix('zkteco-devices')->name('zkteco-devices.')->controller(ZktecoDeviceController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/{device}', 'show')->name('show');
            Route::patch('/{device}/approve', 'approve')->name('approve');
            Route::patch('/{device}/suspend', 'suspend')->name('suspend');
            Route::post('/{device}/reboot', 'reboot')->name('reboot');
            Route::post('/{device}/restart', 'restart')->name('restart');
            Route::post('/{device}/users', 'storeUser')->name('users.store');
            Route::delete('/{device}/users', 'destroyUser')->name('users.destroy');
        });

        Route::get('attendance-logs', [AttendanceLogController::class, 'index'])->name('attendance-logs.index');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    });
});
