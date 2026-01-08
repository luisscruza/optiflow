<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
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
        // Si existe la tabla, borrala
        if (Schema::hasTable('prescriptions')) {
            Schema::dropIfExists('prescriptions');
        }

        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Workspace::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'patient_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->constrained('users');
            $table->foreignIdFor(Contact::class, 'optometrist_id')->nullable()->constrained('contacts');

            // === LENSOMETRÍA ===
            $table->string('lensometria_od', 50)->nullable(); // Ojo Derecho
            $table->string('lensometria_oi', 50)->nullable(); // Ojo Izquierdo
            $table->string('lensometria_add', 50)->nullable(); // Adición

            // === AGUDEZA VISUAL - LEJANA ===
            // Sin Corrección (SC)
            $table->string('av_lejos_sc_od', 20)->nullable(); // 20/
            $table->string('av_lejos_sc_oi', 20)->nullable(); // 20/
            // Con Corrección (CC)
            $table->string('av_lejos_cc_od', 20)->nullable(); // 20/
            $table->string('av_lejos_cc_oi', 20)->nullable(); // 20/
            // Pin Hole (PH)
            $table->string('av_lejos_ph_od', 20)->nullable(); // 20/
            $table->string('av_lejos_ph_oi', 20)->nullable(); // 20/

            // === AGUDEZA VISUAL - CERCANA ===
            // Sin Corrección (SC)
            $table->string('av_cerca_sc_od', 20)->nullable();
            $table->string('av_cerca_sc_oi', 20)->nullable();
            // Con Corrección (CC)
            $table->string('av_cerca_cc_od', 20)->nullable();
            $table->string('av_cerca_cc_oi', 20)->nullable();
            // Pin Hole (PH)
            $table->string('av_cerca_ph_od', 20)->nullable();
            $table->string('av_cerca_ph_oi', 20)->nullable();

            // === BIOMICROSCOPÍA - OJO DERECHO ===
            $table->text('biomicroscopia_od_cejas')->nullable();
            $table->text('biomicroscopia_od_pestanas')->nullable();
            $table->text('biomicroscopia_od_parpados')->nullable();
            $table->text('biomicroscopia_od_conjuntiva')->nullable();
            $table->text('biomicroscopia_od_esclerotica')->nullable();
            $table->text('biomicroscopia_od_cornea')->nullable();
            $table->text('biomicroscopia_od_iris')->nullable();
            $table->text('biomicroscopia_od_pupila')->nullable();
            $table->text('biomicroscopia_od_cristalino')->nullable();

            // === BIOMICROSCOPÍA - OJO IZQUIERDO ===
            $table->text('biomicroscopia_oi_cejas')->nullable();
            $table->text('biomicroscopia_oi_pestanas')->nullable();
            $table->text('biomicroscopia_oi_parpados')->nullable();
            $table->text('biomicroscopia_oi_conjuntiva')->nullable();
            $table->text('biomicroscopia_oi_esclerotica')->nullable();
            $table->text('biomicroscopia_oi_cornea')->nullable();
            $table->text('biomicroscopia_oi_iris')->nullable();
            $table->text('biomicroscopia_oi_pupila')->nullable();
            $table->text('biomicroscopia_oi_cristalino')->nullable();

            // === BIOMICROSCOPÍA - OBSERVACIONES ===
            $table->text('biomicroscopia_observaciones')->nullable();

            // === EXAMEN PUPILAR ===
            // OD (Ojo Derecho)
            $table->enum('pupilar_od_fotomotor_directo', ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'])->nullable();
            $table->enum('pupilar_od_consensual', ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'])->nullable();
            $table->enum('pupilar_od_acomodativo', ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'])->nullable();

            // OI (Ojo Izquierdo)
            $table->enum('pupilar_oi_fotomotor_directo', ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'])->nullable();
            $table->enum('pupilar_oi_consensual', ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'])->nullable();
            $table->enum('pupilar_oi_acomodativo', ['Normoreactivo', 'Hiporreactivo', 'Arreactivo'])->nullable();

            // === OFTALMOSCOPÍA ===
            // OD (Ojo Derecho)
            $table->string('oftalmoscopia_od_color', 100)->nullable();
            $table->string('oftalmoscopia_od_papila', 100)->nullable();
            $table->string('oftalmoscopia_od_excavacion', 50)->nullable();
            $table->string('oftalmoscopia_od_relacion_av', 50)->nullable(); // Relación Arteria/Vena
            $table->string('oftalmoscopia_od_macula', 100)->nullable();
            $table->string('oftalmoscopia_od_brillo_foveal', 100)->nullable();
            $table->string('oftalmoscopia_od_fijacion', 100)->nullable();

            // OI (Ojo Izquierdo)
            $table->string('oftalmoscopia_oi_color', 100)->nullable();
            $table->string('oftalmoscopia_oi_papila', 100)->nullable();
            $table->string('oftalmoscopia_oi_excavacion', 50)->nullable();
            $table->string('oftalmoscopia_oi_relacion_av', 50)->nullable(); // Relación Arteria/Vena
            $table->string('oftalmoscopia_oi_macula', 100)->nullable(); // Avascular
            $table->string('oftalmoscopia_oi_brillo_foveal', 100)->nullable(); // Presente
            $table->string('oftalmoscopia_oi_fijacion', 100)->nullable(); // Central

            // === OFTALMOSCOPÍA - OBSERVACIONES ===
            $table->text('oftalmoscopia_observaciones')->nullable();

            // === QUERATOMETRÍA ===
            $table->string('quera_od_horizontal', 20)->nullable();
            $table->string('quera_od_vertical', 20)->nullable();
            $table->string('quera_od_eje', 20)->nullable();
            $table->string('quera_od_dif', 20)->nullable();
            $table->string('quera_oi_horizontal', 20)->nullable();
            $table->string('quera_oi_vertical', 20)->nullable();
            $table->string('quera_oi_eje', 20)->nullable();
            $table->string('quera_oi_dif', 20)->nullable();

            // === PRESIÓN INTRAOCULAR ===
            $table->string('presion_od', 20)->nullable(); // mmHg
            $table->string('presion_od_hora', 10)->nullable(); // Hora de medición
            $table->string('presion_oi', 20)->nullable(); // mmHg
            $table->string('presion_oi_hora', 10)->nullable(); // Hora de medición

            // === CICLOPLEGIA ===
            $table->string('cicloplegia_medicamento', 100)->nullable();
            $table->string('cicloplegia_num_gotas', 10)->nullable();
            $table->string('cicloplegia_hora_aplicacion', 10)->nullable();
            $table->string('cicloplegia_hora_examen', 10)->nullable();

            // === AUTOREFRACCIÓN ===
            // Ojo Derecho
            $table->string('autorefraccion_od_esfera', 20)->nullable();
            $table->string('autorefraccion_od_cilindro', 20)->nullable();
            $table->string('autorefraccion_od_eje', 20)->nullable();
            // Ojo Izquierdo
            $table->string('autorefraccion_oi_esfera', 20)->nullable();
            $table->string('autorefraccion_oi_cilindro', 20)->nullable();
            $table->string('autorefraccion_oi_eje', 20)->nullable();

            // === REFRACCIÓN ===
            // Ojo Derecho
            $table->string('refraccion_od_esfera', 20)->nullable();
            $table->string('refraccion_od_cilindro', 20)->nullable();
            $table->string('refraccion_od_eje', 20)->nullable();
            // Ojo Izquierdo
            $table->string('refraccion_oi_esfera', 20)->nullable();
            $table->string('refraccion_oi_cilindro', 20)->nullable();
            $table->string('refraccion_oi_eje', 20)->nullable();

            // === RETINOSCOPÍA ===
            // Ojo Derecho
            $table->string('retinoscopia_od_esfera', 20)->nullable();
            $table->string('retinoscopia_od_cilindro', 20)->nullable();
            $table->string('retinoscopia_od_eje', 20)->nullable();
            // Ojo Izquierdo
            $table->string('retinoscopia_oi_esfera', 20)->nullable();
            $table->string('retinoscopia_oi_cilindro', 20)->nullable();
            $table->string('retinoscopia_oi_eje', 20)->nullable();
            // Tipo
            $table->boolean('retinoscopia_estatica')->default(false);
            $table->boolean('retinoscopia_dinamica')->default(false);

            // === SUBJETIVO (en sección de refracción) ===
            // Ojo Derecho
            $table->string('refraccion_subjetivo_od_esfera', 20)->nullable();
            $table->string('refraccion_subjetivo_od_cilindro', 20)->nullable();
            $table->string('refraccion_subjetivo_od_eje', 20)->nullable();
            $table->string('refraccion_subjetivo_od_adicion', 20)->nullable();
            // Ojo Izquierdo
            $table->string('refraccion_subjetivo_oi_esfera', 20)->nullable();
            $table->string('refraccion_subjetivo_oi_cilindro', 20)->nullable();
            $table->string('refraccion_subjetivo_oi_eje', 20)->nullable();
            $table->string('refraccion_subjetivo_oi_adicion', 20)->nullable();

            // === OBSERVACIONES DE REFRACCIÓN ===
            $table->text('refraccion_observaciones')->nullable();

            // === SUBJETIVO ===
            $table->string('subjetivo_od_esfera', 20)->nullable();
            $table->string('subjetivo_od_cilindro', 20)->nullable();
            $table->string('subjetivo_od_eje', 20)->nullable();
            $table->string('subjetivo_od_add', 20)->nullable();
            $table->string('subjetivo_od_dp', 20)->nullable(); // Distancia pupilar
            $table->string('subjetivo_od_av_lejos', 20)->nullable(); // 20/
            $table->string('subjetivo_od_av_cerca', 20)->nullable();

            $table->string('subjetivo_oi_esfera', 20)->nullable();
            $table->string('subjetivo_oi_cilindro', 20)->nullable();
            $table->string('subjetivo_oi_eje', 20)->nullable();
            $table->string('subjetivo_oi_add', 20)->nullable();
            $table->string('subjetivo_oi_dp', 20)->nullable(); // Distancia pupilar
            $table->string('subjetivo_oi_av_lejos', 20)->nullable(); // 20/
            $table->string('subjetivo_oi_av_cerca', 20)->nullable();

            // === TEST'S ===

            // === VISIÓN CROMÁTICA ===
            $table->string('vision_cromatica_test_usado', 100)->nullable();
            $table->text('vision_cromatica_od')->nullable();
            $table->text('vision_cromatica_oi')->nullable();
            $table->text('vision_cromatica_interpretacion')->nullable();

            // === ESTEREOPSIS ===
            $table->string('estereopsis_test_usado', 100)->nullable();
            $table->text('estereopsis_agudeza')->nullable(); // Resultado del test de estereopsis

            // === TONOMETRÍA ===
            $table->string('tonometria_metodo', 100)->nullable(); // Método usado
            $table->string('tonometria_hora', 10)->nullable(); // Hora de la medición
            $table->text('tonometria_tonometro')->nullable(); // Tipo de tonómetro
            $table->text('tonometria_od')->nullable(); // Presión OD
            $table->text('tonometria_oi')->nullable(); // Presión OI

            // === TEST ADICIONALES ===
            $table->text('test_adicionales')->nullable(); // Campo de texto libre para otros tests

            // === MOTILIDAD OCULAR ===

            // === OJO DOMINANTE Y MANO DOMINANTE ===
            $table->enum('ojo_dominante', ['Derecho', 'Izquierdo'])->nullable();
            $table->enum('mano_dominante', ['Derecha', 'Izquierda'])->nullable();

            // === KAPPA ===
            $table->enum('kappa_od', ['Positivo', 'Negativo', 'Neutro'])->nullable();
            $table->enum('kappa_oi', ['Positivo', 'Negativo', 'Neutro'])->nullable();

            // === DUCCIONES ===
            $table->text('ducciones_od')->nullable();
            $table->text('ducciones_oi')->nullable();

            // === HIRSHBERG ===
            $table->string('hirshberg', 100)->nullable();

            // === VERSIONES ===
            // Grid de OK boxes - almacenar como JSON o campos individuales
            $table->json('versiones_grid')->nullable(); // Puede almacenar matriz de OK/valores

            // === TEST USADO (Cover Test) ===
            $table->string('motilidad_test_usado', 100)->nullable();

            // RFP (Sin corrección) y RFN (Con corrección)
            $table->string('motilidad_rfp_vl', 50)->nullable(); // RFP Visión Lejana
            $table->string('motilidad_rfp_vc', 50)->nullable(); // RFP Visión Cercana
            $table->string('motilidad_rfn_vl', 50)->nullable(); // RFN Visión Lejana
            $table->string('motilidad_rfn_vc', 50)->nullable(); // RFN Visión Cercana

            // Saltos Vergenciales
            $table->string('motilidad_saltos_vergenciales_vl', 50)->nullable();
            $table->string('motilidad_saltos_vergenciales_vc', 50)->nullable();

            // === PPC (Punto Próximo de Convergencia) ===
            $table->string('ppc_objeto_real', 50)->nullable();
            $table->string('ppc_luz', 50)->nullable();
            $table->string('ppc_filtro_rojo', 50)->nullable();

            // === LAG (Acomodación y Flexibilidad) ===
            // OD
            $table->string('lag_od_acomodacion', 50)->nullable();
            $table->string('lag_od_flexibilidad', 50)->nullable();
            // OI
            $table->string('lag_oi_acomodacion', 50)->nullable();
            $table->string('lag_oi_flexibilidad', 50)->nullable();

            // ARP (Acomodación Relativa Positiva)
            $table->string('arp_subjetiva', 50)->nullable();
            $table->string('arp_objetiva', 50)->nullable();

            // ARN (Acomodación Relativa Negativa)
            $table->string('arn_amplitud', 50)->nullable();

            // AO (Ambos Ojos)
            $table->string('ao_valor', 50)->nullable();
            $table->string('ao_aa', 50)->nullable(); // A/A column

            // === OBSERVACIONES DE MOTILIDAD ===
            $table->text('motilidad_observaciones')->nullable();

            // === DISPOSICIÓN ===
            $table->text('disposicion')->nullable(); // Se formula lentes para lejos, etc.

            // === OBSERVACIONES ===
            $table->text('observaciones')->nullable(); // Observaciones generales (lo que se imprime)
            $table->text('observaciones_internas')->nullable(); // Observaciones internas (no se imprimen)

            // === RECOMENDACIÓN ===
            $table->text('recomendacion')->nullable();

            // === DIAGNÓSTICO ===
            $table->string('tipo_diagnostico', 100)->nullable(); // Ej: "Impresión diagnóstica"
            $table->string('diagnostico_codigo', 50)->nullable(); // Ej: "CIE10"
            $table->string('diagnostico_tipo', 100)->nullable(); // Ej: "Diagnóstico"
            $table->string('diagnostico_principal', 100)->nullable(); // Ej: "Principal"
            $table->integer('num_dispositivos_medicos')->default(0);
            $table->text('diagnosticos')->nullable(); // Texto libre para diagnósticos múltiples

            $table->json('diagnosticos_cie')->nullable(); // JSON array de diagnósticos múltiples

            // === METADATA ===
            $table->date('proxima_cita')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('patient_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
