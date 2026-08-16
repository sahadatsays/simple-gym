<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Enums\ProductStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Category;
use App\Models\GymSetting;
use App\Models\MembershipPlan;
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
        $this->authorizePermission('reports.view');

        return view('admin.reports.index', [
            'reports' => ReportType::cases(),
        ]);
    }

    public function show(ReportFilterRequest $request, string $report): View|Response|StreamedResponse
    {
        $this->authorizePermission('reports.view');

        $type = ReportType::tryFrom($report);

        if ($type === null) {
            abort(404);
        }

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
            'memberStatuses' => MemberStatus::cases(),
            'productStatuses' => ProductStatus::cases(),
        ]);
    }
}
