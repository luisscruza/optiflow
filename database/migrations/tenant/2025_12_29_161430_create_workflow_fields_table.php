<?php

declare(strict_types=1);

use App\Models\Mastertable;
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
        Schema::create('workflow_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->references('id')->on('workflows')->onDelete('cascade');
            $table->string('name'); // Display name
            $table->string('key')->index(); // Unique key for this workflow (e.g., 'lens_type', 'characteristics')
            $table->string('type'); // 'text', 'select', 'number', 'date', 'textarea'
            $table->foreignIdFor(Mastertable::class)->nullable()->constrained()->onDelete('set null'); // For select type
            $table->boolean('is_required')->default(false);
            $table->string('placeholder')->nullable();
            $table->string('default_value')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workflow_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_fields');
    }
};
