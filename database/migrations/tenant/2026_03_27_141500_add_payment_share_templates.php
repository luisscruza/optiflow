<?php

declare(strict_types=1);

use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use App\Models\ShareTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ShareTemplate::query()->firstOrCreate(
            [
                'entity_type' => ShareTemplateEntity::Payment,
                'channel' => ShareTemplateChannel::Email,
            ],
            [
                'name' => 'Correo de pago',
                'subject' => 'Recibo de pago {{payment.payment_number}}',
                'body' => "Hola {{contact.name}},\n\nTe compartimos tu recibo de pago {{payment.payment_number}}.\n\nPuedes verlo aqui:\n{{shareable_link}}\n\nMonto: {{payment.amount}}\nFecha: {{payment.payment_date}}",
                'is_active' => true,
            ],
        );

        ShareTemplate::query()->firstOrCreate(
            [
                'entity_type' => ShareTemplateEntity::Payment,
                'channel' => ShareTemplateChannel::WhatsApp,
            ],
            [
                'name' => 'WhatsApp de pago',
                'subject' => null,
                'body' => 'Hola {{contact.name}}, te compartimos tu recibo de pago {{payment.payment_number}} aqui: {{shareable_link}}. Monto: {{payment.amount}}.',
                'is_active' => true,
            ],
        );
    }
};
