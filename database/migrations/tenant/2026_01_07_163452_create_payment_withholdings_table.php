<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Models\WithholdingType;
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
        Schema::create('payment_withholdings', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Payment::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(WithholdingType::class)->constrained()->restrictOnDelete();
            $table->decimal('base_amount', 12, 2)->default(0); // Monto base sobre el que se aplica
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_withholdings');
    }
};
