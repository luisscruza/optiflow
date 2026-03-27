<?php

declare(strict_types=1);

use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use App\Models\ShareTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->string('channel');
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['entity_type', 'channel']);
        });

        ShareTemplate::query()->forceCreate([
            'entity_type' => ShareTemplateEntity::Invoice,
            'channel' => ShareTemplateChannel::Email,
            'name' => 'Correo de factura',
            'subject' => 'Factura #{{invoice.document_number}}',
            'body' => "Hola {{contact.name}},\n\nTe compartimos tu factura {{invoice.document_number}}.\n\nPuedes verla aquí:\n{{shareable_link}}\n\nTotal: {{invoice.total_amount}}\nVence: {{invoice.due_date}}",
            'is_active' => true,
        ]);

        ShareTemplate::query()->forceCreate([
            'entity_type' => ShareTemplateEntity::Invoice,
            'channel' => ShareTemplateChannel::WhatsApp,
            'name' => 'WhatsApp de factura',
            'subject' => null,
            'body' => 'Hola {{contact.name}}, te compartimos tu factura {{invoice.document_number}}. Puedes verla aquí: {{shareable_link}}. Total: {{invoice.total_amount}}.',
            'is_active' => true,
        ]);

        ShareTemplate::query()->forceCreate([
            'entity_type' => ShareTemplateEntity::Quotation,
            'channel' => ShareTemplateChannel::Email,
            'name' => 'Correo de cotización',
            'subject' => 'Cotización #{{quotation.document_number}}',
            'body' => "Hola {{contact.name}},\n\nTe compartimos la cotización {{quotation.document_number}}.\n\nPuedes verla aquí:\n{{shareable_link}}\n\nTotal: {{quotation.total_amount}}\nValida hasta: {{quotation.due_date}}",
            'is_active' => true,
        ]);

        ShareTemplate::query()->forceCreate([
            'entity_type' => ShareTemplateEntity::Quotation,
            'channel' => ShareTemplateChannel::WhatsApp,
            'name' => 'WhatsApp de cotización',
            'subject' => null,
            'body' => 'Hola {{contact.name}}, te compartimos la cotización {{quotation.document_number}}. Puedes verla aquí: {{shareable_link}}. Total: {{quotation.total_amount}}.',
            'is_active' => true,
        ]);

        ShareTemplate::query()->forceCreate([
            'entity_type' => ShareTemplateEntity::Prescription,
            'channel' => ShareTemplateChannel::Email,
            'name' => 'Correo de receta',
            'subject' => 'Receta óptica #{{prescription.id}}',
            'body' => "Hola {{contact.name}},\n\nTe compartimos tu receta óptica.\n\nPuedes verla aquí:\n{{shareable_link}}\n\nFecha: {{prescription.created_at}}",
            'is_active' => true,
        ]);

        ShareTemplate::query()->forceCreate([
            'entity_type' => ShareTemplateEntity::Prescription,
            'channel' => ShareTemplateChannel::WhatsApp,
            'name' => 'WhatsApp de receta',
            'subject' => null,
            'body' => 'Hola {{contact.name}}, te compartimos tu receta óptica aquí: {{shareable_link}}.',
            'is_active' => true,
        ]);
    }
};
