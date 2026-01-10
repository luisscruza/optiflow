<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface ReportContract
{
    /**
     * Get the report name.
     */
    public function name(): string;

    /**
     * Get the report description.
     */
    public function description(): string;

    /**
     * Get the available filters for this report.
     *
     * @return array<ReportFilter>
     */
    public function filters(): array;

    /**
     * Get the columns for this report.
     *
     * @return array<ReportColumn>
     */
    public function columns(): array;

    /**
     * Build the query for this report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder;

    /**
     * Execute the report and return paginated results.
     *
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator;

    /**
     * Get the raw data without pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array;

    /**
     * Get summary/aggregate data for the report.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array;

    /**
     * Export the report to Excel.
     *
     * @param  array<string, mixed>  $filters
     */
    public function toExcel(array $filters = []): \Symfony\Component\HttpFoundation\BinaryFileResponse;
}
