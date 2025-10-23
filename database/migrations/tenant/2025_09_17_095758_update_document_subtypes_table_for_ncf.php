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
        Schema::table('document_subtypes', function (Blueprint $table): void {
            // NCF fields for Dominican Republic fiscal compliance
            $table->boolean('is_default')->default(false)->after('name'); // If this is the default NCF type
            $table->date('valid_until_date')->nullable()->after('is_default'); // Fecha de vencimiento
            $table->string('prefix', 3)->after('valid_until_date'); // NCF prefix (e.g., "B01", "B02")
            $table->unsignedBigInteger('start_number')->default(1)->after('prefix'); // Número inicial
            $table->unsignedBigInteger('end_number')->nullable()->after('start_number'); // Número final
            $table->unsignedBigInteger('next_number')->default(1)->after('end_number'); // Next number to assign (current)

            // Remove the old sequence field as we'll handle this with next_number
            $table->dropColumn('sequence');

            // Add indexes for performance
            $table->index(['is_default']);
            $table->index(['valid_until_date']);
            $table->index(['prefix']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_subtypes', function (Blueprint $table): void {
            // Drop indexes first
            $table->dropIndex(['is_default']);
            $table->dropIndex(['valid_until_date']);
            $table->dropIndex(['prefix']);

            // Add back sequence field
            $table->string('sequence')->default('1');

            // Drop NCF columns
            $table->dropColumn([
                'is_default',
                'valid_until_date',
                'prefix',
                'start_number',
                'end_number',
                'next_number',
            ]);
        });
    }
};
