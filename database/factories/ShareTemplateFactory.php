<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use App\Models\ShareTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShareTemplate>
 */
final class ShareTemplateFactory extends Factory
{
    protected $model = ShareTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_type' => ShareTemplateEntity::Invoice,
            'channel' => ShareTemplateChannel::Email,
            'name' => 'Correo de factura',
            'subject' => 'Factura {{invoice.document_number}}',
            'body' => 'Hola {{contact.name}}, revisa aqui tu documento: {{shareable_link}}',
            'is_active' => true,
        ];
    }
}
