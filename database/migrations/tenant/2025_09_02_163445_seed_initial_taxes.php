<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('taxes')->insert([
            ['name' => 'ITBIS 18%', 'rate' => 18, 'is_default' => true],
            ['name' => 'ITBIS 16%', 'rate' => 16, 'is_default' => false],
        ]);
    }
};
