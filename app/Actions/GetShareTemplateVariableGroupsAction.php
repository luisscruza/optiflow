<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ShareTemplateEntity;

final class GetShareTemplateVariableGroupsAction
{
    /**
     * @return array<string, list<array{label: string, token: string, description: string}>>
     */
    public function handle(): array
    {
        return [
            ShareTemplateEntity::Invoice->value => [
                ...$this->baseVariables(),
                ['label' => 'Factura: Numero', 'token' => '{{invoice.document_number}}', 'description' => 'Numero del documento.'],
                ['label' => 'Factura: Total', 'token' => '{{invoice.total_amount}}', 'description' => 'Monto total formateado.'],
                ['label' => 'Factura: Emision', 'token' => '{{invoice.issue_date}}', 'description' => 'Fecha de emision.'],
                ['label' => 'Factura: Vencimiento', 'token' => '{{invoice.due_date}}', 'description' => 'Fecha de vencimiento.'],
            ],
            ShareTemplateEntity::Quotation->value => [
                ...$this->baseVariables(),
                ['label' => 'Cotizacion: Numero', 'token' => '{{quotation.document_number}}', 'description' => 'Numero del documento.'],
                ['label' => 'Cotizacion: Total', 'token' => '{{quotation.total_amount}}', 'description' => 'Monto total formateado.'],
                ['label' => 'Cotizacion: Emision', 'token' => '{{quotation.issue_date}}', 'description' => 'Fecha de emision.'],
                ['label' => 'Cotizacion: Vencimiento', 'token' => '{{quotation.due_date}}', 'description' => 'Fecha de vencimiento.'],
            ],
            ShareTemplateEntity::Prescription->value => [
                ...$this->baseVariables(),
                ['label' => 'Paciente: Nombre', 'token' => '{{patient.name}}', 'description' => 'Nombre del paciente.'],
                ['label' => 'Receta: Numero', 'token' => '{{prescription.id}}', 'description' => 'Identificador de la receta.'],
                ['label' => 'Receta: Fecha', 'token' => '{{prescription.created_at}}', 'description' => 'Fecha de creacion.'],
                ['label' => 'Receta: Proximo control', 'token' => '{{prescription.next_control_date}}', 'description' => 'Fecha de proximo control visual.'],
            ],
            ShareTemplateEntity::Payment->value => [
                ...$this->baseVariables(),
                ['label' => 'Pago: Numero', 'token' => '{{payment.payment_number}}', 'description' => 'Numero del recibo.'],
                ['label' => 'Pago: Monto', 'token' => '{{payment.amount}}', 'description' => 'Monto total formateado.'],
                ['label' => 'Pago: Fecha', 'token' => '{{payment.payment_date}}', 'description' => 'Fecha del pago.'],
                ['label' => 'Pago: Metodo', 'token' => '{{payment.payment_method}}', 'description' => 'Metodo de pago legible.'],
                ['label' => 'Factura relacionada', 'token' => '{{invoice.document_number}}', 'description' => 'Numero de factura asociada, si existe.'],
            ],
        ];
    }

    /**
     * @return list<array{label: string, token: string, description: string}>
     */
    private function baseVariables(): array
    {
        return [
            ['label' => 'Enlace compartible', 'token' => '{{shareable_link}}', 'description' => 'Enlace publico al PDF firmado por 30 dias.'],
            ['label' => 'Contacto: Nombre', 'token' => '{{contact.name}}', 'description' => 'Nombre del contacto o paciente.'],
            ['label' => 'Contacto: Correo', 'token' => '{{contact.email}}', 'description' => 'Correo del contacto.'],
            ['label' => 'Contacto: Telefono', 'token' => '{{contact.phone}}', 'description' => 'Telefono principal del contacto.'],
            ['label' => 'Sucursal', 'token' => '{{workspace.name}}', 'description' => 'Nombre de la sucursal.'],
        ];
    }
}
