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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('contact_type', ['customer', 'supplier', 'both'])->default('customer');
            $table->string('phone_primary')->nullable()->after('email');
            $table->string('phone_secondary')->nullable()->after('phone_primary');
            $table->string('mobile')->nullable()->after('phone_secondary');
            $table->string('fax')->nullable()->after('mobile');
            $table->string('identification_type')->nullable()->after('name');
            $table->string('identification_number')->nullable()->after('identification_type');
            $table->string('status')->default('active')->after('contact_type');
            $table->text('observations')->nullable()->after('status');
            $table->decimal('credit_limit', 15, 2)->default(0)->after('observations');
            $table->index(['identification_type', 'identification_number']);
            $table->index('status');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'contact_type']);
            $table->index(['workspace_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
