<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mastertable;
use Illuminate\Database\Seeder;

final class MastertableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mastertables = [
            [
                'name' => 'Motivos de consulta',
                'alias' => 'motivos_consulta',
                'description' => 'Lista de motivos comunes por los cuales los pacientes acuden a consulta oftalmológica.',
                'items' => [
                    'Cambio de lentes',
                    'Visión borrosa de lejos',
                    'Visión borrosa de cerca',
                    'Dolor de cabeza',
                    'Lagrimeo excesivo',
                    'Hiperemia (Ojos rojos)',
                    'Diplopia (visión doble)',
                    'Fatiga ocular',
                    'Otro(s)',
                ],
            ],
            [
                'name' => 'Estado general de salud actual',
                'alias' => 'estado_salud_actual',
                'description' => 'Condiciones médicas comunes que pueden afectar la salud ocular del paciente.',
                'items' => [
                    'Alergias',
                    'Migrañas',
                    'Diabetes',
                    'Hipertensión',
                    'Otro(s)',
                ],
            ],
            [
                'name' => 'Historia ocular familiar',
                'alias' => 'historia_ocular_familiar',
                'description' => 'Condiciones oculares hereditarias que pueden estar presentes en la familia del paciente.',
                'items' => [
                    'Catarata',
                    'Glaucoma',
                    'Ceguera',
                    'Estrabismo',
                    'Defectos Refractivos',
                    'Otro(s)',
                ],
            ],
            // Tipos de lentes
            [
                'name' => 'Tipos de lentes',
                'alias' => 'tipos_de_lentes',
                'description' => 'Diferentes tipos de lentes que pueden ser recomendados según las necesidades del paciente.',
                'items' => [
                    'Visión sencilla',
                    'Flat Top',
                    'Invisibles',
                    'Lentes progresivos',
                    'N/A',
                ],
            ],
            // Tipos de monturas
            [
                'name' => 'Tipos de montura',
                'alias' => 'tipos_de_montura',
                'description' => 'Diferentes estilos de monturas para lentes que pueden ser recomendados.',
                'items' => [
                    'Montura cuadrada',
                    'Montura redonda',
                    'Montura propia',
                    'Montura al aire',
                    'Montura semi al aire',
                    'Montura rectangular',
                    'N/A',
                ],
            ],
            [
                'name' => 'Tipos de gotas',
                'alias' => 'tipos_de_gotas',
                'description' => 'Diferentes tipos de gotas oftálmicas que pueden ser recomendadas para el tratamiento ocular.',
                'items' => [
                    'N/A',
                    'Refresh Optive',
                    'Refresh Tears',
                    'Manzanilla Sophia',
                    'Humylub PF',
                    'Lagricel PF',
                    'Naphacel Ofteno',
                    'Relestat',
                    'Visionace',
                    'Multivita',
                    'Blasfero Shampo',
                    'Nodalerg',
                    'Lutein',
                ],
            ],
        ];

        foreach ($mastertables as $tableData) {
            $mastertable = Mastertable::query()->create([
                'name' => $tableData['name'],
                'alias' => $tableData['alias'],
                'description' => $tableData['description'],
            ]);

            foreach ($tableData['items'] as $itemName) {
                $mastertable->items()->create(['name' => $itemName]);
            }
        }
    }
}
