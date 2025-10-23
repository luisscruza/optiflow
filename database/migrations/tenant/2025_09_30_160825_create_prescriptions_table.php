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
            $table->string('lensometria_od')->nullable(); // Ojo Derecho
            $table->string('lensometria_oi')->nullable(); // Ojo Izquierdo
            $table->string('lensometria_add')->nullable(); // Adición

            // === AGUDEZA VISUAL - LEJANA ===
            // Sin Corrección (SC)
            $table->string('av_lejos_sc_od')->nullable(); // 20/
            $table->string('av_lejos_sc_oi')->nullable(); // 20/
            // Con Corrección (CC)
            $table->string('av_lejos_cc_od')->nullable(); // 20/
            $table->string('av_lejos_cc_oi')->nullable(); // 20/
            // Pin Hole (PH)
            $table->string('av_lejos_ph_od')->nullable(); // 20/
            $table->string('av_lejos_ph_oi')->nullable(); // 20/

            // === AGUDEZA VISUAL - CERCANA ===
            // Sin Corrección (SC)
            $table->string('av_cerca_sc_od')->nullable();
            $table->string('av_cerca_sc_oi')->nullable();
            // Con Corrección (CC)
            $table->string('av_cerca_cc_od')->nullable();
            $table->string('av_cerca_cc_oi')->nullable();
            // Pin Hole (PH)
            $table->string('av_cerca_ph_od')->nullable();
            $table->string('av_cerca_ph_oi')->nullable();

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
            $table->string('oftalmoscopia_od_color')->nullable();
            $table->string('oftalmoscopia_od_papila')->nullable();
            $table->string('oftalmoscopia_od_excavacion')->nullable();
            $table->string('oftalmoscopia_od_relacion_av')->nullable(); // Relación Arteria/Vena
            $table->string('oftalmoscopia_od_macula')->nullable();
            $table->string('oftalmoscopia_od_brillo_foveal')->nullable();
            $table->string('oftalmoscopia_od_fijacion')->nullable();

            // OI (Ojo Izquierdo)
            $table->string('oftalmoscopia_oi_color')->nullable();
            $table->string('oftalmoscopia_oi_papila')->nullable();
            $table->string('oftalmoscopia_oi_excavacion')->nullable();
            $table->string('oftalmoscopia_oi_relacion_av')->nullable(); // Relación Arteria/Vena
            $table->string('oftalmoscopia_oi_macula')->nullable(); // Avascular
            $table->string('oftalmoscopia_oi_brillo_foveal')->nullable(); // Presente
            $table->string('oftalmoscopia_oi_fijacion')->nullable(); // Central

            // === OFTALMOSCOPÍA - OBSERVACIONES ===
            $table->text('oftalmoscopia_observaciones')->nullable();

            // === QUERATOMETRÍA ===
            $table->string('quera_od_horizontal')->nullable();
            $table->string('quera_od_vertical')->nullable();
            $table->string('quera_od_eje')->nullable();
            $table->string('quera_od_dif')->nullable();
            $table->string('quera_oi_horizontal')->nullable();
            $table->string('quera_oi_vertical')->nullable();
            $table->string('quera_oi_eje')->nullable();
            $table->string('quera_oi_dif')->nullable();

            // === PRESIÓN INTRAOCULAR ===
            $table->string('presion_od')->nullable(); // mmHg
            $table->string('presion_od_hora')->nullable(); // Hora de medición
            $table->string('presion_oi')->nullable(); // mmHg
            $table->string('presion_oi_hora')->nullable(); // Hora de medición

            // === CICLOPLEGIA ===
            $table->string('cicloplegia_medicamento')->nullable();
            $table->string('cicloplegia_num_gotas')->nullable();
            $table->string('cicloplegia_hora_aplicacion')->nullable();
            $table->string('cicloplegia_hora_examen')->nullable();

            // === AUTOREFRACCIÓN ===
            // Ojo Derecho
            $table->string('autorefraccion_od_esfera')->nullable();
            $table->string('autorefraccion_od_cilindro')->nullable();
            $table->string('autorefraccion_od_eje')->nullable();
            // Ojo Izquierdo
            $table->string('autorefraccion_oi_esfera')->nullable();
            $table->string('autorefraccion_oi_cilindro')->nullable();
            $table->string('autorefraccion_oi_eje')->nullable();

            // === REFRACCIÓN ===
            // Ojo Derecho
            $table->string('refraccion_od_esfera')->nullable();
            $table->string('refraccion_od_cilindro')->nullable();
            $table->string('refraccion_od_eje')->nullable();
            // Ojo Izquierdo
            $table->string('refraccion_oi_esfera')->nullable();
            $table->string('refraccion_oi_cilindro')->nullable();
            $table->string('refraccion_oi_eje')->nullable();

            // === RETINOSCOPÍA ===
            // Ojo Derecho
            $table->string('retinoscopia_od_esfera')->nullable();
            $table->string('retinoscopia_od_cilindro')->nullable();
            $table->string('retinoscopia_od_eje')->nullable();
            // Ojo Izquierdo
            $table->string('retinoscopia_oi_esfera')->nullable();
            $table->string('retinoscopia_oi_cilindro')->nullable();
            $table->string('retinoscopia_oi_eje')->nullable();
            // Tipo
            $table->boolean('retinoscopia_estatica')->default(false);
            $table->boolean('retinoscopia_dinamica')->default(false);

            // === SUBJETIVO (en sección de refracción) ===
            // Ojo Derecho
            $table->string('refraccion_subjetivo_od_esfera')->nullable();
            $table->string('refraccion_subjetivo_od_cilindro')->nullable();
            $table->string('refraccion_subjetivo_od_eje')->nullable();
            $table->string('refraccion_subjetivo_od_adicion')->nullable();
            // Ojo Izquierdo
            $table->string('refraccion_subjetivo_oi_esfera')->nullable();
            $table->string('refraccion_subjetivo_oi_cilindro')->nullable();
            $table->string('refraccion_subjetivo_oi_eje')->nullable();
            $table->string('refraccion_subjetivo_oi_adicion')->nullable();

            // === OBSERVACIONES DE REFRACCIÓN ===
            $table->text('refraccion_observaciones')->nullable();

            // === SUBJETIVO ===
            $table->string('subjetivo_od_esfera')->nullable();
            $table->string('subjetivo_od_cilindro')->nullable();
            $table->string('subjetivo_od_eje')->nullable();
            $table->string('subjetivo_od_add')->nullable();
            $table->string('subjetivo_od_dp')->nullable(); // Distancia pupilar
            $table->string('subjetivo_od_av_lejos')->nullable(); // 20/
            $table->string('subjetivo_od_av_cerca')->nullable();

            $table->string('subjetivo_oi_esfera')->nullable();
            $table->string('subjetivo_oi_cilindro')->nullable();
            $table->string('subjetivo_oi_eje')->nullable();
            $table->string('subjetivo_oi_add')->nullable();
            $table->string('subjetivo_oi_dp')->nullable(); // Distancia pupilar
            $table->string('subjetivo_oi_av_lejos')->nullable(); // 20/
            $table->string('subjetivo_oi_av_cerca')->nullable();

            // === TEST'S ===

            // === VISIÓN CROMÁTICA ===
            $table->string('vision_cromatica_test_usado')->nullable();
            $table->text('vision_cromatica_od')->nullable();
            $table->text('vision_cromatica_oi')->nullable();
            $table->text('vision_cromatica_interpretacion')->nullable();

            // === ESTEREOPSIS ===
            $table->string('estereopsis_test_usado')->nullable();
            $table->text('estereopsis_agudeza')->nullable(); // Resultado del test de estereopsis

            // === TONOMETRÍA ===
            $table->string('tonometria_metodo')->nullable(); // Método usado
            $table->string('tonometria_hora')->nullable(); // Hora de la medición
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
            $table->string('hirshberg')->nullable();

            // === VERSIONES ===
            // Grid de OK boxes - almacenar como JSON o campos individuales
            $table->json('versiones_grid')->nullable(); // Puede almacenar matriz de OK/valores

            // === TEST USADO (Cover Test) ===
            $table->string('motilidad_test_usado')->nullable();

            // RFP (Sin corrección) y RFN (Con corrección)
            $table->string('motilidad_rfp_vl')->nullable(); // RFP Visión Lejana
            $table->string('motilidad_rfp_vc')->nullable(); // RFP Visión Cercana
            $table->string('motilidad_rfn_vl')->nullable(); // RFN Visión Lejana
            $table->string('motilidad_rfn_vc')->nullable(); // RFN Visión Cercana

            // Saltos Vergenciales
            $table->string('motilidad_saltos_vergenciales_vl')->nullable();
            $table->string('motilidad_saltos_vergenciales_vc')->nullable();

            // === PPC (Punto Próximo de Convergencia) ===
            $table->string('ppc_objeto_real')->nullable();
            $table->string('ppc_luz')->nullable();
            $table->string('ppc_filtro_rojo')->nullable();

            // === LAG (Acomodación y Flexibilidad) ===
            // OD
            $table->string('lag_od_acomodacion')->nullable();
            $table->string('lag_od_flexibilidad')->nullable();
            // OI
            $table->string('lag_oi_acomodacion')->nullable();
            $table->string('lag_oi_flexibilidad')->nullable();

            // ARP (Acomodación Relativa Positiva)
            $table->string('arp_subjetiva')->nullable();
            $table->string('arp_objetiva')->nullable();

            // ARN (Acomodación Relativa Negativa)
            $table->string('arn_amplitud')->nullable();

            // AO (Ambos Ojos)
            $table->string('ao_valor')->nullable();
            $table->string('ao_aa')->nullable(); // A/A column

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
            $table->string('tipo_diagnostico')->nullable(); // Ej: "Impresión diagnóstica"
            $table->string('diagnostico_codigo')->nullable(); // Ej: "CIE10"
            $table->string('diagnostico_tipo')->nullable(); // Ej: "Diagnóstico"
            $table->string('diagnostico_principal')->nullable(); // Ej: "Principal"
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
            $table->index('fecha_examen');
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
