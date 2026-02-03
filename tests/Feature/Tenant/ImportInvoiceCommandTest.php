<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('imports invoice items with multiple taxes from csv', function (): void {
    Currency::factory()->create();
    User::factory()->create(['id' => 1]);

    $path = storage_path('app/import-invoice-command-test.csv');

    $header = 'FECHA,DOCUMENT_NUMBER,ESTADO,WORKSPACE_NAME,WORKSPACE_ID,CLIENTE,IDENTIFICADOR,TELEFONO1,VENCIMIENTO,VENDEDOR,NOTAS,PRODUCTO/SERVICIO - NOMBRE,PRODUCTO/SERVICIO - REFERENCIA,CANTIDAD,PRECIO UNITARIO,DESCUENTO,IMPUESTO_1_NOMBRE,IMPUESTO_1_PORCENTAJE,IMPUESTO_1_VALOR,IMPUESTO_2_NOMBRE,IMPUESTO_2_PORCENTAJE,IMPUESTO_2_VALOR,IMPUESTO_3_NOMBRE,IMPUESTO_3_PORCENTAJE,IMPUESTO_3_VALOR,PRODUCTO/SERVICIO - TOTAL,SUBTOTAL - PRODUCTOS/SERVICIOS,TOTAL - FACTURA';
    $row = implode(',', [
        '7/10/22',
        'B0100000001',
        'Por cobrar',
        'Tienda Test',
        'Ten001',
        'Test Client',
        '0',
        '8090000000',
        '22/10/2022',
        'Test Seller',
        'Test note',
        'Test Product',
        'SKU123',
        '1',
        '100',
        '0',
        'ITBIS',
        '18',
        '18',
        'OTHER',
        '2',
        '2',
        '',
        '',
        '',
        '120',
        '100',
        '120',
    ]);

    File::put($path, $header."\n".$row);

    Artisan::call('import:invoices', [
        'file' => $path,
        '--limit' => 1,
    ]);

    $documentSubtype = DocumentSubtype::query()->where('prefix', 'B01')->first();

    expect($documentSubtype)->not->toBeNull();

    $invoice = Invoice::query()->where('document_number', 'B0100000001')->first();

    expect($invoice)->not->toBeNull();

    $workspace = Workspace::query()->where('code', 'Ten001')->first();

    expect($workspace)->not->toBeNull();
    expect($invoice->workspace_id)->toBe($workspace->id);

    $item = $invoice->items()->first();

    expect($item)->not->toBeNull();

    $item->load('taxes');

    expect($item->taxes)->toHaveCount(2);
    expect($item->tax_amount)->toBeCloseTo(20.0, 0.01);

    $itbis = $item->taxes->firstWhere('name', 'ITBIS');
    $other = $item->taxes->firstWhere('name', 'OTHER');

    expect($itbis)->not->toBeNull();
    expect($other)->not->toBeNull();

    expect((float) $itbis->pivot->rate)->toBeCloseTo(18.0, 0.01);
    expect((float) $itbis->pivot->amount)->toBeCloseTo(18.0, 0.01);
    expect((float) $other->pivot->rate)->toBeCloseTo(2.0, 0.01);
    expect((float) $other->pivot->amount)->toBeCloseTo(2.0, 0.01);

    File::delete($path);
});
