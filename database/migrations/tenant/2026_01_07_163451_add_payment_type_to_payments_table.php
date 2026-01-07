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
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('payment_type')->default('invoice_payment')->after('id'); // invoice_payment, other_income
            $table->string('payment_number')->nullable()->after('payment_type');
            $table->foreignIdFor(Contact::class)->nullable()->after('currency_id')->constrained()->nullOnDelete();
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal_amount');
            $table->decimal('withholding_amount', 12, 2)->default(0)->after('tax_amount');
            $table->string('status')->default('completed')->after('note'); // completed, voided

            $table->index('payment_type');
            $table->index('payment_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['payment_number']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'payment_type',
                'payment_number',
                'contact_id',
                'subtotal_amount',
                'tax_amount',
                'withholding_amount',
                'status',
            ]);
        });
    }
};
