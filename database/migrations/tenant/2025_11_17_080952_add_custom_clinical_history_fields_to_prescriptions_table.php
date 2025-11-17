<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            // Add custom text fields for clinical history sections
            $table->text('motivos_consulta_otros')->nullable()->after('optometrist_id');
            $table->text('estado_salud_actual_otros')->nullable()->after('motivos_consulta_otros');
            $table->text('historia_ocular_familiar_otros')->nullable()->after('estado_salud_actual_otros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'motivos_consulta_otros',
                'estado_salud_actual_otros',
                'historia_ocular_familiar_otros',
            ]);
        });
    }
};
