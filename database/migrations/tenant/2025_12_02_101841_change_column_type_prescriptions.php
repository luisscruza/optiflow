<?php

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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('pupilar_od_fotomotor_directo')->nullable()->change();
            $table->string('pupilar_od_consensual')->nullable()->change();
            $table->string('pupilar_od_acomodativo')->nullable()->change();

            // OI (Ojo Izquierdo)
            $table->string('pupilar_oi_fotomotor_directo')->nullable()->change();
            $table->string('pupilar_oi_consensual')->nullable()->change();
            $table->string('pupilar_oi_acomodativo')->nullable()->change();

            $table->string('ojo_dominante')->nullable()->change();
            $table->string('mano_dominante')->nullable()->change();

            $table->string('kappa_od')->nullable()->change();
            $table->string('kappa_oi')->nullable()->change();
        });
    }
};
