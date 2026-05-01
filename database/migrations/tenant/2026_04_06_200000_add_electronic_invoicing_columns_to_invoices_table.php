<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('easyfactu_invoice_id')->nullable()->after('notes')->index();
            $table->string('encf')->nullable()->after('easyfactu_invoice_id');
            $table->string('dgii_status')->nullable()->after('encf');
            $table->string('dgii_track_id')->nullable()->after('dgii_status');
            $table->string('dgii_security_code')->nullable()->after('dgii_track_id');
            $table->text('dgii_qr_code_url')->nullable()->after('dgii_security_code');
            $table->string('dgii_environment')->nullable()->after('dgii_qr_code_url');
            $table->boolean('is_electronic')->default(false)->after('dgii_environment');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'easyfactu_invoice_id',
                'encf',
                'dgii_status',
                'dgii_track_id',
                'dgii_security_code',
                'dgii_qr_code_url',
                'dgii_environment',
                'is_electronic',
            ]);
        });
    }
};
