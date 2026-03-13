<?php

declare(strict_types=1);

use App\Models\ProductRecipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('mastertables')->updateOrInsert(
            ['alias' => ProductRecipe::PRODUCTS_MASTERTABLE_ALIAS],
            [
                'name' => 'Productos recetarios',
                'description' => 'Listado configurable de productos para recetarios.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mastertables')
            ->where('alias', ProductRecipe::PRODUCTS_MASTERTABLE_ALIAS)
            ->delete();
    }
};
