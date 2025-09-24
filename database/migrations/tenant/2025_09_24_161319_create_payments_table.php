<?php

declare(strict_types=1);

use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Invoice;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BankAccount::class);
            $table->foreignIdFor(Currency::class);
            $table->foreignIdFor(Invoice::class)->nullable();
            $table->date('payment_date');
            $table->string('payment_method');
            $table->float('amount');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('payment_date');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
