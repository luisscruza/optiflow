<?php

declare(strict_types=1);

use App\Models\Prescription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\File;

test('imports prescriptions from comma delimited csv with debug output', function (): void {
    User::factory()->create([
        'business_role' => 'admin',
    ]);

    $path = storage_path('app/import-prescription-command-test.csv');

    $header = 'id,created_at,updated_at,Sucursal,Cliente,distancia_ao,distancia_naso,altura,agudeza_visual,Lente recomendado,Gota recomendada,Montura recomendada,Examinador,Observaciones generales,esfera_od,esfera_oi,cilindro_od,cilindro_oi,eje_od,eje_oi,adicion_od,adicion_oi,comentario,estado_actual,historia_ocular,Canal,CODIGO_SUCURSAL';
    $row = implode(',', [
        '1056',
        '2023-01-08 18:22:03',
        '2023-12-19 09:23:31',
        'Optica COVI Salcedo',
        'Paciente Demo',
        '68 mm',
        '34/34',
        'N/A',
        '20/80',
        'Vision Sencilla',
        'N/A',
        'Montura propia',
        'Receta Externa',
        'Observacion demo',
        'PL',
        'PL',
        '-0.50',
        '-1.00',
        '153',
        '17',
        '',
        '0',
        '',
        '[]',
        '[]',
        'Otro(s)',
        'Sal001',
    ]);

    File::put($path, $header."\n".$row);

    $this->artisan('import:prescriptions', [
        'file' => $path,
        '--limit' => 1,
        '--debug' => true,
    ])
        ->expectsOutputToContain('Delimitador detectado: ,')
        ->expectsOutputToContain('Total columnas detectadas: 27')
        ->expectsOutputToContain('Importadas: 1')
        ->assertSuccessful();

    $workspace = Workspace::query()->where('code', 'Sal001')->first();

    expect($workspace)->not->toBeNull();
    expect(Prescription::query()->count())->toBe(1);
    expect(Prescription::query()->first()?->workspace_id)->toBe($workspace->id);

    File::delete($path);
});
