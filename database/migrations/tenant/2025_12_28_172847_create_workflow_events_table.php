<?php

declare(strict_types=1);

use App\Models\User;
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
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_job_id')->references('id')->on('workflow_jobs')->onDelete('cascade');
            $table->foreignUuid('from_stage_id')->nullable()->references('id')->on('workflow_stages')->onDelete('restrict');
            $table->foreignUuid('to_stage_id')->nullable()->references('id')->on('workflow_stages')->onDelete('restrict');
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
