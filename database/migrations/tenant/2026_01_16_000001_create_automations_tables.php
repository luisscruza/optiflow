<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Workspace::class)->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('published_version')->default(1);
            $table->timestamps();
        });

        Schema::create('automation_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('automation_id')->references('id')->on('automations')->onDelete('cascade');
            $table->unsignedInteger('version');
            $table->json('definition');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['automation_id', 'version']);
        });

        Schema::create('automation_triggers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('automation_id')->references('id')->on('automations')->onDelete('cascade');
            $table->foreignIdFor(Workspace::class)->constrained()->onDelete('cascade');
            $table->string('event_key');
            $table->uuid('workflow_id')->nullable();
            $table->uuid('workflow_stage_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'event_key'], 'atr_ws_event_idx');
            $table->index(['workspace_id', 'event_key', 'workflow_stage_id'], 'atr_ws_event_stage_idx');
        });

        Schema::create('automation_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('automation_id')->references('id')->on('automations')->onDelete('cascade');
            $table->foreignId('automation_version_id')->constrained('automation_versions')->onDelete('restrict');
            $table->foreignIdFor(Workspace::class)->constrained()->onDelete('cascade');

            $table->string('trigger_event_key');
            $table->string('subject_type');
            $table->string('subject_id');

            $table->string('status')->default('running');
            $table->unsignedInteger('pending_nodes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['automation_id', 'created_at']);
        });

        Schema::create('automation_node_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('automation_run_id')->references('id')->on('automation_runs')->onDelete('cascade');

            $table->string('node_id');
            $table->string('node_type');
            $table->string('status')->default('running');
            $table->unsignedInteger('attempts')->default(0);
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_run_id', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_node_runs');
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('automation_triggers');
        Schema::dropIfExists('automation_versions');
        Schema::dropIfExists('automations');
    }
};
