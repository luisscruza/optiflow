<?php

declare(strict_types=1);

use App\Models\MastertableItem;
use App\Models\Prescription;
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
        Schema::create('prescription_item', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Prescription::class)->cascadeOnDelete();
            $table->foreignIdFor(MastertableItem::class)->cascadeOnDelete();
            $table->string('mastertable_alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_item');
    }
};
