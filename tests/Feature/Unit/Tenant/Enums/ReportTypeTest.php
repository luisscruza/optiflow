<?php

declare(strict_types=1);

use App\Enums\ReportGroup;
use App\Enums\ReportType;

it('keeps sales and expenses report metadata available after the merge', function (): void {
    expect(ReportType::CustomersByBranch->label())->toBe('Clientes por sucursal')
        ->and(ReportType::CustomersByBranch->description())
        ->toBe('Exporta clientes consolidando las sucursales donde tuvieron facturas o recetas en el rango seleccionado.')
        ->and(ReportType::CustomersByBranch->group())->toBe(ReportGroup::Sales)
        ->and(ReportType::ExpensesSummary->label())->toBe('Gastos generales')
        ->and(ReportType::ExpensesSummary->description())
        ->toBe('Consulta los gastos registrados por suplidor, sucursal, estado e impuestos.')
        ->and(ReportType::ExpensesSummary->group())->toBe(ReportGroup::Expenses);
});
