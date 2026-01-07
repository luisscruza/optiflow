<?php

declare(strict_types=1);

use App\Models\ChartAccount;
use App\Models\Payment;
use App\Models\PaymentConcept;
use App\Models\Tax;
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
        Schema::create('payment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Payment::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ChartAccount::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(PaymentConcept::class)->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->foreignIdFor(Tax::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_lines');
    }
};
