<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\MastertableItem;
use App\Models\User;
use App\Models\Workspace;
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
        Schema::create('product_recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Workspace::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->constrained('users');
            $table->foreignIdFor(Contact::class)->constrained('contacts')->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'optometrist_id')->constrained('contacts');
            $table->foreignIdFor(MastertableItem::class, 'product_id')->constrained('mastertable_items');
            $table->text('indication')->nullable();
            $table->timestamps();

            $table->index('contact_id');
            $table->index('product_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
