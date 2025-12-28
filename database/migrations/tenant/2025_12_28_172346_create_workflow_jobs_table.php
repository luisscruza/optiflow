<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Workflow;
use App\Models\WorkflowStage;
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
            $table->id();
            $table->foreignIdFor(Workflow::class)->constrained()->onDelete('restrict');
            $table->foreignIdFor(WorkflowStage::class)->constrained()->onDelete('restrict');
            $table->foreignIdFor(Invoice::class)->constrained()->onDelete('restrict');
            $table->foreignIdFor(Contact::class)->constrained()->onDelete('restrict');
            $table->string('priority')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->text('notes')->nullable();
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
