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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Workspace::class);
            $table->foreignIdFor(Contact::class);
            $table->foreignIdFor(User::class, 'created_by');
            $table->text('observations')->nullable();
            $table->text('observations_internal')->nullable();
            // Campos de la receta...
            $table->string('esfera_oi')->nullable();
            $table->string('cilindro_oi')->nullable();
            $table->string('eje_oi')->nullable();
            $table->string('adicion_oi')->nullable();
            $table->string('esfera_od')->nullable();
            $table->string('cilindro_od')->nullable();
            $table->string('eje_od')->nullable();
            $table->string('adicion_od')->nullable();
            $table->string('distancia_ao')->nullable();
            $table->string('distancia_naso')->nullable();
            $table->string('altura')->nullable();
            $table->timestamps();
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
