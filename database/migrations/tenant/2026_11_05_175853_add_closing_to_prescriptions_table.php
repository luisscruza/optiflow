<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Intentionally empty: adding columns to `prescriptions` exceeds MySQL's row size limit
     * on production tenants. Closing data lives in `prescription_closures` instead
     * (see 2026_11_05_180939_move_prescription_closing_to_closures_table).
     */
    public function up(): void {}

    public function down(): void {}
};
