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
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->restrictOnDelete();
            $table->string('document_number');
            $table->date('issue_date');
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('itbis_amount', 15, 2)->default(0);
            $table->decimal('isc_amount', 15, 2)->default(0);
            $table->decimal('withheld_itbis_amount', 15, 2)->default(0);
            $table->decimal('withheld_isr_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_informal')->default(false);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'contact_id', 'document_number'], 'expenses_workspace_contact_document_unique');
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'issue_date']);
            $table->index('is_informal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
