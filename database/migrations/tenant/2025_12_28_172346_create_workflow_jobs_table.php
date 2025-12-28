<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Invoice;
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
        Schema::create('workflow_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->references('id')->on('workflows')->onDelete('restrict');
            $table->foreignUuid('workflow_stage_id')->references('id')->on('workflow_stages')->onDelete('restrict');
            $table->foreignIdFor(Invoice::class)->nullable()->constrained()->onDelete('restrict');
            $table->foreignIdFor(Contact::class)->nullable()->constrained()->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->string('priority')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_jobs');
    }
};
