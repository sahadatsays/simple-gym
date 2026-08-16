<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetMaintenanceType;
use App\Enums\AssetStatus;
use App\Enums\MemberStatus;
use App\Enums\ProductStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\AssetCategory;
use App\Models\Category;
use App\Models\GymSetting;
use App\Models\InvestmentCategory;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\ReportService;
use App\Support\ReportExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private ReportExporter $exporter,
    ) {}

    public function index(): View
    {
        $this->authorizeReportsHubAccess();

        /** @var User $user */
        $user = auth()->user();

        $reports = collect(ReportType::cases())
            ->filter(fn (ReportType $type): bool => $this->userCanViewReport($user, $type))
            ->values();

        return view('admin.reports.index', [
            'reports' => $reports,
        ]);
    }

    public function show(ReportFilterRequest $request, string $report): View|Response|StreamedResponse
    {
        $type = ReportType::tryFrom($report);

        if ($type === null) {
            abort(404);
        }

        $this->authorizeReportAccess($type);

        $filters = $request->filters($type);
        $export = $request->exportFormat();
        $perPage = $export === null ? config('gym.pagination.per_page') : null;

        $payload = $this->reports->build($type, $filters, $perPage);

        if ($export === 'print') {
            return view('admin.reports.print', [
                'type' => $type,
                'payload' => $payload,
                'filters' => $filters,
            ]);
        }

        if ($export === 'pdf') {
            $currency = GymSetting::query()->value('currency') ?? config('gym.defaults.currency');

            return $this->exporter->pdf($type, $payload, $filters, $currency);
        }

        if ($export === 'excel') {
            return $this->exporter->excel($type, $payload, $filters);
        }

        return view('admin.reports.show', [
            'type' => $type,
            'payload' => $payload,
            'filters' => $filters,
            'membershipPlans' => MembershipPlan::query()->orderBy('name')->get(['id', 'name']),
            'productCategories' => Category::query()->ordered()->get(['id', 'name']),
            'investmentCategories' => InvestmentCategory::query()->ordered()->get(['id', 'name']),
            'assetCategories' => AssetCategory::query()->ordered()->get(['id', 'name']),
            'memberStatuses' => MemberStatus::cases(),
            'productStatuses' => ProductStatus::cases(),
            'assetStatuses' => AssetStatus::cases(),
            'maintenanceTypes' => AssetMaintenanceType::cases(),
        ]);
    }

    private function authorizeReportsHubAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->can('reports.view') || $user?->can('asset-investment-reports.view'),
            403
        );
    }

    private function authorizeReportAccess(ReportType $type): void
    {
        if ($type->isAssetInvestmentReport()) {
            $this->authorizePermission('asset-investment-reports.view');

            return;
        }

        $this->authorizePermission('reports.view');
    }

    private function userCanViewReport(User $user, ReportType $type): bool
    {
        if ($type->isAssetInvestmentReport()) {
            return $user->can('asset-investment-reports.view');
        }

        return $user->can('reports.view');
    }
}
