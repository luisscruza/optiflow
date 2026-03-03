<?php

declare(strict_types=1);

use App\Models\Contact;
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
        Schema::create('contact_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignId('related_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'related_contact_id']);
            $table->index('related_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_relationships');
    }
};
