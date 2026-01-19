<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Automation\AutomationEngine;
use App\Services\Automation\NodeRunners\ConditionNodeRunner;
use App\Services\Automation\NodeRunners\HttpWebhookNodeRunner;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use App\Services\Automation\NodeRunners\TelegramMessageNodeRunner;
use App\Services\Automation\NodeRunners\WhatsappMessageNodeRunner;
use App\Services\Automation\NodeTypes\NodeTypeDefinition;
use App\Services\Automation\NodeTypes\NodeTypeRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NodeRunnerRegistry::class, function () {
            $registry = new NodeRunnerRegistry();

            $registry->register(new HttpWebhookNodeRunner());
            $registry->register(new TelegramMessageNodeRunner());
            $registry->register(new WhatsappMessageNodeRunner());
            $registry->register(new ConditionNodeRunner());

            return $registry;
        });

        $this->app->singleton(NodeTypeRegistry::class, function () {
            $registry = new NodeTypeRegistry();

            $this->registerTriggers($registry);
            $this->registerActions($registry);
            $this->registerConditions($registry);

            return $registry;
        });

        $this->app->singleton(AutomationEngine::class);
    }

    public function boot(AutomationEngine $engine): void
    {
        Event::listen('workflow.job.stage_changed', function (array $payload) use ($engine): void {
            $engine->handleWorkflowJobStageChanged($payload);
        });

        Event::listen('invoice.created', function (array $payload) use ($engine): void {
            $engine->handleInvoiceCreated($payload);
        });

        Event::listen('invoice.updated', function (array $payload) use ($engine): void {
            $engine->handleInvoiceUpdated($payload);
        });
    }

    private function registerTriggers(NodeTypeRegistry $registry): void
    {
        $registry->register(new NodeTypeDefinition(
            key: 'workflow.stage_entered',
            category: 'trigger',
            label: 'Cambio de etapa',
            description: 'Se ejecuta cuando un proceso entra a una etapa específica',
            icon: 'Zap',
            color: 'amber',
            reactNodeType: 'trigger',
            defaultConfig: [
                'workflow_id' => '',
                'stage_id' => '',
            ],
            allowMultiple: false,
            showInPalette: false,
            eventKey: 'workflow.job.stage_changed',
            inspectorComponent: 'TriggerConfig',
            outputSchema: [
                'job' => ['type' => 'object', 'description' => 'Datos del proceso/trabajo'],
                'job.id' => ['type' => 'string', 'description' => 'ID del proceso'],
                'job.notes' => ['type' => 'string', 'description' => 'Notas del proceso'],
                'job.priority' => ['type' => 'string', 'description' => 'Prioridad del proceso'],
                'job.due_date' => ['type' => 'string', 'description' => 'Fecha de vencimiento'],
                'contact' => ['type' => 'object', 'description' => 'Contacto asociado'],
                'contact.id' => ['type' => 'string', 'description' => 'ID del contacto'],
                'contact.name' => ['type' => 'string', 'description' => 'Nombre del contacto'],
                'contact.email' => ['type' => 'string', 'description' => 'Email del contacto'],
                'contact.phone' => ['type' => 'string', 'description' => 'Teléfono del contacto'],
                'stage' => ['type' => 'object', 'description' => 'Etapa actual'],
                'stage.id' => ['type' => 'string', 'description' => 'ID de la etapa'],
                'stage.name' => ['type' => 'string', 'description' => 'Nombre de la etapa'],
                'from_stage' => ['type' => 'object', 'description' => 'Etapa anterior (si aplica)'],
                'from_stage.id' => ['type' => 'string', 'description' => 'ID de la etapa anterior'],
                'from_stage.name' => ['type' => 'string', 'description' => 'Nombre de la etapa anterior'],
            ],
        ));

        $registry->register(new NodeTypeDefinition(
            key: 'invoice.created',
            category: 'trigger',
            label: 'Factura creada',
            description: 'Se ejecuta cuando se crea una nueva factura',
            icon: 'FileText',
            color: 'amber',
            reactNodeType: 'trigger',
            defaultConfig: [],
            allowMultiple: false,
            showInPalette: false,
            eventKey: 'invoice.created',
            inspectorComponent: 'TriggerConfig',
            outputSchema: [
                'invoice' => ['type' => 'object', 'description' => 'Datos de la factura'],
                'invoice.id' => ['type' => 'string', 'description' => 'ID de la factura'],
                'invoice.invoice_number' => ['type' => 'string', 'description' => 'Número de factura'],
                'invoice.ncf' => ['type' => 'string', 'description' => 'NCF de la factura'],
                'invoice.subtotal' => ['type' => 'number', 'description' => 'Subtotal'],
                'invoice.tax_amount' => ['type' => 'number', 'description' => 'Monto de impuestos'],
                'invoice.total' => ['type' => 'number', 'description' => 'Total de la factura'],
                'invoice.status' => ['type' => 'string', 'description' => 'Estado de la factura'],
                'invoice.issue_date' => ['type' => 'string', 'description' => 'Fecha de emisión'],
                'invoice.due_date' => ['type' => 'string', 'description' => 'Fecha de vencimiento'],
                'contact' => ['type' => 'object', 'description' => 'Contacto/cliente de la factura'],
                'contact.id' => ['type' => 'string', 'description' => 'ID del contacto'],
                'contact.name' => ['type' => 'string', 'description' => 'Nombre del contacto'],
                'contact.email' => ['type' => 'string', 'description' => 'Email del contacto'],
                'contact.phone' => ['type' => 'string', 'description' => 'Teléfono del contacto'],
                'contact.rnc' => ['type' => 'string', 'description' => 'RNC/Cédula del contacto'],
            ],
        ));

        $registry->register(new NodeTypeDefinition(
            key: 'invoice.updated',
            category: 'trigger',
            label: 'Factura actualizada',
            description: 'Se ejecuta cuando se actualiza una factura',
            icon: 'FileEdit',
            color: 'amber',
            reactNodeType: 'trigger',
            defaultConfig: [],
            allowMultiple: false,
            showInPalette: false,
            eventKey: 'invoice.updated',
            inspectorComponent: 'TriggerConfig',
            outputSchema: [
                'invoice' => ['type' => 'object', 'description' => 'Datos de la factura'],
                'invoice.id' => ['type' => 'string', 'description' => 'ID de la factura'],
                'invoice.invoice_number' => ['type' => 'string', 'description' => 'Número de factura'],
                'invoice.ncf' => ['type' => 'string', 'description' => 'NCF de la factura'],
                'invoice.subtotal' => ['type' => 'number', 'description' => 'Subtotal'],
                'invoice.tax_amount' => ['type' => 'number', 'description' => 'Monto de impuestos'],
                'invoice.total' => ['type' => 'number', 'description' => 'Total de la factura'],
                'invoice.status' => ['type' => 'string', 'description' => 'Estado de la factura'],
                'invoice.issue_date' => ['type' => 'string', 'description' => 'Fecha de emisión'],
                'invoice.due_date' => ['type' => 'string', 'description' => 'Fecha de vencimiento'],
                'contact' => ['type' => 'object', 'description' => 'Contacto/cliente de la factura'],
                'contact.id' => ['type' => 'string', 'description' => 'ID del contacto'],
                'contact.name' => ['type' => 'string', 'description' => 'Nombre del contacto'],
                'contact.email' => ['type' => 'string', 'description' => 'Email del contacto'],
                'contact.phone' => ['type' => 'string', 'description' => 'Teléfono del contacto'],
                'contact.rnc' => ['type' => 'string', 'description' => 'RNC/Cédula del contacto'],
            ],
        ));
    }

    private function registerActions(NodeTypeRegistry $registry): void
    {
        $registry->register(new NodeTypeDefinition(
            key: 'http.webhook',
            category: 'action',
            label: 'Petición HTTP',
            description: 'Envía una solicitud HTTP a una URL externa',
            icon: 'Webhook',
            color: 'blue',
            reactNodeType: 'webhook',
            defaultConfig: [
                'url' => '',
                'method' => 'POST',
                'headers' => [],
                'body' => [],
            ],
            inspectorComponent: 'WebhookConfig',
            outputSchema: [
                'response' => ['type' => 'object', 'description' => 'Respuesta del servidor'],
                'response.status' => ['type' => 'number', 'description' => 'Código de estado HTTP'],
                'response.body' => ['type' => 'mixed', 'description' => 'Cuerpo de la respuesta'],
                'response.headers' => ['type' => 'object', 'description' => 'Headers de la respuesta'],
            ],
        ));

        $registry->register(new NodeTypeDefinition(
            key: 'telegram.send_message',
            category: 'action',
            label: 'Telegram',
            description: 'Envía un mensaje por Telegram',
            icon: 'Send',
            color: 'sky',
            reactNodeType: 'telegram',
            defaultConfig: [
                'bot_token' => '',
                'chat_id' => '',
                'message' => '',
                'parse_mode' => 'HTML',
                'disable_notification' => false,
            ],
            inspectorComponent: 'TelegramConfig',
            outputSchema: [
                'message_id' => ['type' => 'number', 'description' => 'ID del mensaje enviado'],
                'chat_id' => ['type' => 'string', 'description' => 'ID del chat donde se envió'],
                'sent_at' => ['type' => 'string', 'description' => 'Fecha y hora de envío'],
            ],
        ));

        $registry->register(new NodeTypeDefinition(
            key: 'whatsapp.send_message',
            category: 'action',
            label: 'WhatsApp Cloud API',
            description: 'Envía un mensaje por WhatsApp Cloud API',
            icon: 'MessageCircle',
            color: 'green',
            reactNodeType: 'whatsapp',
            defaultConfig: [
                'whatsapp_account_id' => '',
                'action' => 'send_message',
                'to' => '',
                'message' => '',
                'preview_url' => false,
            ],
            inspectorComponent: 'WhatsappConfig',
            outputSchema: [
                'message_id' => ['type' => 'string', 'description' => 'ID del mensaje enviado'],
                'to' => ['type' => 'string', 'description' => 'Número de destino'],
                'sent_at' => ['type' => 'string', 'description' => 'Fecha y hora de envío'],
            ],
        ));
    }

    private function registerConditions(NodeTypeRegistry $registry): void
    {
        $registry->register(new NodeTypeDefinition(
            key: 'logic.condition',
            category: 'condition',
            label: 'Condición',
            description: 'Bifurca el flujo según una condición',
            icon: 'GitBranch',
            color: 'purple',
            reactNodeType: 'condition',
            defaultConfig: [
                'field' => '',
                'operator' => 'equals',
                'value' => '',
            ],
            inspectorComponent: 'ConditionConfig',
            outputSchema: [
                'result' => ['type' => 'boolean', 'description' => 'Resultado de la condición (true/false)'],
                'matched_value' => ['type' => 'mixed', 'description' => 'Valor que fue evaluado'],
            ],
        ));
    }
}
