<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WorkflowJob;
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
        Schema::create('workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(WorkflowJob::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(WorkflowStage::class, 'from_stage_id')->nullable()->constrained()->onDelete('restrict');
            $table->foreignIdFor(WorkflowStage::class, 'to_stage_id')->nullable()->constrained()->onDelete('restrict');
            $table->string('event_type');
            $table->foreignIdFor(User::class)->nullable()->constrained()->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_events');
    }
};
