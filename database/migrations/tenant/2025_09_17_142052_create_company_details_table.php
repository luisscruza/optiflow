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
        Schema::create('company_details', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        $fields = [
            ['key' => 'company_name', 'value' => 'My Company'],
            ['key' => 'address', 'value' => '123 Main St, City, Country'],
            ['key' => 'phone', 'value' => '+1 234 567 890'],
            ['key' => 'email', 'value' => 'info@mycompany.com'],
            ['key' => 'tax_id', 'value' => ''],
            ['key' => 'currency', 'value' => 1],
            ['key' => 'logo', 'value' => ''],
        ];

        foreach ($fields as $field) {
            DB::table('company_details')->insert($field);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_details');
    }
};
