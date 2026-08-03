<?php

namespace App\Support;

use App\Enums\ReportType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExporter
{
    /**
     * @param  array{
     *     summary: array<string, float|int|string|null>,
     *     rows: Collection<int, array<string, mixed>>|LengthAwarePaginator<int, array<string, mixed>>,
     *     columns: array<int, array{key: string, label: string, align?: string}>
     * }  $payload
     * @param  array<string, mixed>  $filters
     */
    public function pdf(ReportType $type, array $payload, array $filters, string $currency): Response
    {
        $rows = $this->normalizeRows($payload['rows']);

        $pdf = Pdf::loadView('admin.reports.exports.pdf', [
            'type' => $type,
            'payload' => $payload,
            'rows' => $rows,
            'filters' => $filters,
            'currency' => $currency,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->filename($type, 'pdf'));
    }

    /**
     * @param  array{
     *     summary: array<string, float|int|string|null>,
     *     rows: Collection<int, array<string, mixed>>|LengthAwarePaginator<int, array<string, mixed>>,
     *     columns: array<int, array{key: string, label: string, align?: string}>
     * }  $payload
     * @param  array<string, mixed>  $filters
     */
    public function excel(ReportType $type, array $payload, array $filters): StreamedResponse
    {
        $rows = $this->normalizeRows($payload['rows']);
        $columns = $payload['columns'];

        return response()->streamDownload(function () use ($rows, $columns, $type, $filters): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [$type->label()]);
            fputcsv($handle, ['From', $filters['from_date'] ?? '—', 'To', $filters['to_date'] ?? '—']);
            fputcsv($handle, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, collect($columns)->pluck('label')->all());

            foreach ($rows as $row) {
                fputcsv($handle, collect($columns)->map(fn (array $column): mixed => $row[$column['key']] ?? '')->all());
            }

            fclose($handle);
        }, $this->filename($type, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filename(ReportType $type, string $extension): string
    {
        return sprintf('%s-%s.%s', $type->value, now()->format('Ymd-His'), $extension);
    }

    /**
     * @param  Collection<int, array<string, mixed>>|LengthAwarePaginator<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizeRows($rows): Collection
    {
        if ($rows instanceof LengthAwarePaginator) {
            return collect($rows->items());
        }

        return collect($rows);
    }
}
