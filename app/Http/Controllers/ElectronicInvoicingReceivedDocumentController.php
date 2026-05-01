<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Exceptions\EasyFactuException;
use App\Models\User;
use App\Services\EasyFactuService;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ElectronicInvoicingReceivedDocumentController
{
    public function index(Request $request, #[CurrentUser] User $user, EasyFactuService $easyFactu): Response
    {
        abort_unless($user->can(Permission::ElectronicInvoicingView), 403);

        $filters = $this->resolveFilters($request);

        $suppliers = [];

        try {
            $suppliersResponse = $easyFactu->getSuppliers();
            $suppliers = collect($suppliersResponse['suppliers'] ?? [])
                ->filter(fn ($supplier): bool => is_array($supplier))
                ->map(fn (array $supplier): array => [
                    'id' => $supplier['id'] ?? null,
                    'rnc' => $supplier['rnc'] ?? null,
                    'name' => $supplier['name'] ?? null,
                ])
                ->filter(fn (array $supplier): bool => (bool) $supplier['id'] && (bool) $supplier['name'])
                ->values()
                ->all();
        } catch (EasyFactuException) {
            $suppliers = [];
        }

        try {
            $response = $easyFactu->getReceivedDocumentsWithFilters($filters);
        } catch (EasyFactuException $exception) {
            $appliedFilters = [
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'supplier_id' => $filters['supplier_id'] ?? null,
                'search' => $filters['search'] ?? null,
                'status' => $filters['status'] ?? null,
                'per_page' => (int) ($filters['per_page'] ?? 25),
            ];

            return Inertia::render('electronic-invoicing/received/index', [
                'documents' => $this->buildTableResource([], $suppliers, [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => (int) ($filters['per_page'] ?? 25),
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                    'first_page_url' => null,
                    'last_page_url' => null,
                    'next_page_url' => null,
                    'prev_page_url' => null,
                    'path' => route('electronic-invoicing.received.index'),
                    'links' => [],
                ], $appliedFilters),
                'suppliers' => $suppliers,
                'summary' => [
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                ],
                'filters' => $appliedFilters,
                'error' => $exception->getMessage(),
            ]);
        }

        $documents = collect($response['data'] ?? $response['documents'] ?? [])
            ->map(fn (array $document): array => $this->mapSummaryDocument($document))
            ->values()
            ->all();

        $summaryPayload = is_array($response['summary'] ?? null) ? $response['summary'] : null;

        $summary = [
            'subtotal' => $summaryPayload !== null
                ? (float) ($summaryPayload['subtotal'] ?? 0)
                : (float) collect($documents)->sum(fn (array $document): float => (float) ($document['subtotal'] ?? 0)),
            'tax_amount' => $summaryPayload !== null
                ? (float) ($summaryPayload['tax_amount'] ?? 0)
                : (float) collect($documents)->sum(fn (array $document): float => (float) ($document['tax_amount'] ?? 0)),
            'total_amount' => $summaryPayload !== null
                ? (float) ($summaryPayload['total_amount'] ?? 0)
                : (float) collect($documents)->sum(fn (array $document): float => (float) ($document['total_amount'] ?? 0)),
        ];

        $responseFilters = is_array($response['filters'] ?? null) ? $response['filters'] : [];

        $appliedFilters = [
            'from' => $responseFilters['from'] ?? $filters['from'] ?? null,
            'to' => $responseFilters['to'] ?? $filters['to'] ?? null,
            'supplier_id' => $responseFilters['supplier_id'] ?? $filters['supplier_id'] ?? null,
            'search' => $responseFilters['search'] ?? $filters['search'] ?? null,
            'status' => $responseFilters['status'] ?? $filters['status'] ?? null,
            'per_page' => (int) ($responseFilters['per_page'] ?? $filters['per_page'] ?? 25),
        ];

        $pagination = [
            'current_page' => (int) ($response['current_page'] ?? 1),
            'last_page' => (int) ($response['last_page'] ?? 1),
            'per_page' => (int) ($response['per_page'] ?? $appliedFilters['per_page']),
            'total' => (int) ($response['total'] ?? count($documents)),
            'from' => $response['from'] ?? null,
            'to' => $response['to'] ?? null,
            'first_page_url' => $response['first_page_url'] ?? null,
            'last_page_url' => $response['last_page_url'] ?? null,
            'next_page_url' => $response['next_page_url'] ?? null,
            'prev_page_url' => $response['prev_page_url'] ?? null,
            'path' => $response['path'] ?? route('electronic-invoicing.received.index'),
            'links' => $response['links'] ?? [],
        ];

        return Inertia::render('electronic-invoicing/received/index', [
            'documents' => $this->buildTableResource($documents, $suppliers, $pagination, $appliedFilters),
            'suppliers' => $suppliers,
            'summary' => $summary,
            'filters' => $appliedFilters,
            'error' => null,
        ]);
    }

    public function export(Request $request, #[CurrentUser] User $user, EasyFactuService $easyFactu): StreamedResponse|RedirectResponse
    {
        abort_unless($user->can(Permission::ElectronicInvoicingView), 403);

        $filters = $this->resolveFilters($request);

        try {
            $documents = $this->fetchAllDocumentsForExport($easyFactu, $filters);
        } catch (EasyFactuException $exception) {
            return redirect()
                ->route('electronic-invoicing.received.index', $request->query())
                ->with('error', 'No se pudo exportar el CSV: '.$exception->getMessage());
        }

        $filename = 'received-documents-'.CarbonImmutable::now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($documents): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'RECEIVED_AT',
                'ENCF',
                'ECF_TYPE',
                'SUPPLIER_NAME',
                'SUPPLIER_RNC',
                'BUYER_NAME',
                'BUYER_RNC',
                'ISSUE_DATE',
                'CURRENCY',
                'SUBTOTAL',
                'TAX_AMOUNT',
                'TOTAL_AMOUNT',
                'STATUS',
            ]);

            foreach ($documents as $document) {
                $status = (string) ($document['status'] ?? 'received');

                fputcsv($output, [
                    (string) ($document['received_at'] ?? ''),
                    (string) ($document['encf'] ?? ''),
                    (string) ($document['ecf_type'] ?? ''),
                    (string) ($document['supplier']['name'] ?? ''),
                    (string) ($document['supplier']['rnc'] ?? ''),
                    (string) ($document['buyer_name'] ?? ''),
                    (string) ($document['buyer_rnc'] ?? ''),
                    (string) ($document['issue_date'] ?? ''),
                    (string) ($document['currency'] ?? 'DOP'),
                    number_format((float) ($document['subtotal'] ?? 0), 2, '.', ''),
                    number_format((float) ($document['tax_amount'] ?? 0), 2, '.', ''),
                    number_format((float) ($document['total_amount'] ?? 0), 2, '.', ''),
                    $this->mapStatusLabel($status),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(string $receivedDocument, #[CurrentUser] User $user, EasyFactuService $easyFactu): Response|RedirectResponse
    {
        abort_unless($user->can(Permission::ElectronicInvoicingView), 403);

        try {
            $response = $easyFactu->getReceivedDocument($receivedDocument);
        } catch (EasyFactuException $exception) {
            return redirect()->route('electronic-invoicing.received.index')
                ->with('error', 'No se pudo cargar el documento recibido: '.$exception->getMessage());
        }

        $document = $response['document'] ?? null;

        if (! is_array($document)) {
            return redirect()->route('electronic-invoicing.received.index')
                ->with('error', 'No se encontró el documento recibido solicitado.');
        }

        return Inertia::render('electronic-invoicing/received/show', [
            'document' => $this->mapDetailDocument($document),
        ]);
    }

    public function print(string $receivedDocument, #[CurrentUser] User $user, EasyFactuService $easyFactu): Response|RedirectResponse
    {
        abort_unless($user->can(Permission::ElectronicInvoicingView), 403);

        try {
            $response = $easyFactu->getReceivedDocument($receivedDocument);
        } catch (EasyFactuException $exception) {
            return redirect()->route('electronic-invoicing.received.index')
                ->with('error', 'No se pudo cargar la representación impresa: '.$exception->getMessage());
        }

        $document = $response['document'] ?? null;

        if (! is_array($document)) {
            return redirect()->route('electronic-invoicing.received.index')
                ->with('error', 'No se encontró el documento recibido solicitado.');
        }

        return Inertia::render('electronic-invoicing/received/print', [
            'document' => $this->mapDetailDocument($document),
        ]);
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function mapSummaryDocument(array $document): array
    {
        return [
            'id' => $document['id'] ?? null,
            'ecf_type' => $document['ecf_type'] ?? null,
            'encf' => $document['encf'] ?? null,
            'issue_date' => $document['issue_date'] ?? null,
            'buyer_rnc' => $document['buyer_rnc'] ?? null,
            'buyer_name' => $document['buyer_name'] ?? null,
            'currency' => $document['currency'] ?? 'DOP',
            'subtotal' => $document['subtotal'] ?? 0,
            'tax_amount' => $document['tax_amount'] ?? 0,
            'total_amount' => $document['total_amount'] ?? 0,
            'status' => $document['status'] ?? null,
            'received_at' => $document['received_at'] ?? null,
            'supplier' => is_array($document['supplier'] ?? null) ? [
                'id' => $document['supplier']['id'] ?? null,
                'rnc' => $document['supplier']['rnc'] ?? null,
                'name' => $document['supplier']['name'] ?? null,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function mapDetailDocument(array $document): array
    {
        return [
            ...$this->mapSummaryDocument($document),
            'created_at' => $document['created_at'] ?? null,
            'updated_at' => $document['updated_at'] ?? null,
            'qr_code_url' => $document['qr_code_url'] ?? null,
            'security_code' => $document['security_code'] ?? null,
            'signed_at' => $document['signed_at'] ?? null,
            'supplier' => is_array($document['supplier'] ?? null) ? [
                'id' => $document['supplier']['id'] ?? null,
                'rnc' => $document['supplier']['rnc'] ?? null,
                'name' => $document['supplier']['name'] ?? null,
                'email' => $document['supplier']['email'] ?? null,
                'address' => $document['supplier']['address'] ?? null,
                'phone' => $document['supplier']['phone'] ?? null,
            ] : null,
            'items' => collect($document['items'] ?? [])
                ->filter(fn ($item): bool => is_array($item))
                ->map(fn (array $item): array => [
                    'id' => $item['id'] ?? null,
                    'line_number' => $item['line_number'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total_amount' => $item['total_amount'] ?? 0,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'from_start' => ['nullable', 'date'],
            'from_end' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $now = CarbonImmutable::now();
        $defaultFrom = $now->startOfMonth()->toDateString();
        $defaultTo = $now->toDateString();

        $filters['from'] = (string) ($filters['from'] ?? $filters['from_start'] ?? $defaultFrom);
        $filters['to'] = (string) ($filters['to'] ?? $filters['from_end'] ?? $defaultTo);
        $filters['per_page'] = (int) ($filters['per_page'] ?? 25);
        $filters['supplier_id'] = ($filters['supplier_id'] ?? null) === 'all' ? null : ($filters['supplier_id'] ?? null);
        $filters['status'] = ($filters['status'] ?? null) === 'all' ? null : ($filters['status'] ?? null);

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     *
     * @throws EasyFactuException
     */
    private function fetchAllDocumentsForExport(EasyFactuService $easyFactu, array $filters): array
    {
        $filters['page'] = 1;
        $filters['per_page'] = 100;

        $response = $easyFactu->getReceivedDocumentsWithFilters($filters);
        $documents = collect($response['data'] ?? $response['documents'] ?? [])
            ->filter(fn ($document): bool => is_array($document))
            ->values();

        $lastPage = max(1, (int) ($response['last_page'] ?? 1));

        for ($page = 2; $page <= $lastPage; $page++) {
            $pageResponse = $easyFactu->getReceivedDocumentsWithFilters([
                ...$filters,
                'page' => $page,
            ]);

            $pageDocuments = collect($pageResponse['data'] ?? $pageResponse['documents'] ?? [])
                ->filter(fn ($document): bool => is_array($document));

            $documents = $documents->concat($pageDocuments);
        }

        return $documents
            ->map(fn (array $document): array => $this->mapSummaryDocument($document))
            ->values()
            ->all();
    }

    private function mapStatusLabel(string $status): string
    {
        return match ($status) {
            'processed' => 'Procesado',
            'accepted' => 'Aceptado',
            'rejected' => 'Rechazado',
            default => 'Recibido',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @param  array<int, array<string, mixed>>  $suppliers
     * @param  array<string, mixed>  $pagination
     * @param  array<string, mixed>  $appliedFilters
     * @return array<string, mixed>
     */
    private function buildTableResource(array $documents, array $suppliers, array $pagination, array $appliedFilters): array
    {
        $rows = collect($documents)
            ->map(function (array $document): array {
                $status = (string) ($document['status'] ?? 'received');

                return [
                    ...$document,
                    'supplier_name' => $document['supplier']['name'] ?? null,
                    'supplier_rnc' => $document['supplier']['rnc'] ?? null,
                    'status' => [
                        'value' => $status,
                        'label' => $this->mapStatusLabel($status),
                        'variant' => 'secondary',
                        'className' => match ($status) {
                            'processed' => 'bg-amber-100 text-amber-700 hover:bg-amber-100',
                            'accepted' => 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100',
                            'rejected' => 'bg-red-100 text-red-700 hover:bg-red-100',
                            default => 'bg-blue-100 text-blue-700 hover:bg-blue-100',
                        },
                    ],
                    'actions' => [
                        [
                            'name' => 'view',
                            'label' => 'Ver',
                            'icon' => 'eye',
                            'href' => route('electronic-invoicing.received.show', ['receivedDocument' => $document['id']]),
                            'isInline' => true,
                        ],
                    ],
                ];
            })
            ->values()
            ->all();

        $supplierOptions = collect($suppliers)
            ->map(function (array $supplier): array {
                return [
                    'value' => (string) $supplier['id'],
                    'label' => $supplier['rnc'] ? "{$supplier['name']} ({$supplier['rnc']})" : (string) $supplier['name'],
                ];
            })
            ->sortBy('label')
            ->values()
            ->all();

        return [
            'data' => [
                'data' => $rows,
                'current_page' => (int) ($pagination['current_page'] ?? 1),
                'last_page' => (int) ($pagination['last_page'] ?? 1),
                'per_page' => (int) ($pagination['per_page'] ?? 25),
                'total' => (int) ($pagination['total'] ?? count($rows)),
                'from' => $pagination['from'] ?? null,
                'to' => $pagination['to'] ?? null,
                'first_page_url' => $pagination['first_page_url'] ?? null,
                'last_page_url' => $pagination['last_page_url'] ?? null,
                'next_page_url' => $pagination['next_page_url'] ?? null,
                'prev_page_url' => $pagination['prev_page_url'] ?? null,
                'path' => $pagination['path'] ?? route('electronic-invoicing.received.index'),
                'links' => $pagination['links'] ?? [],
            ],
            'columns' => [
                ['key' => 'received_at', 'label' => 'Recepción', 'type' => 'date', 'sortable' => false],
                ['key' => 'encf', 'label' => 'e-NCF', 'type' => 'text', 'sortable' => false],
                ['key' => 'ecf_type', 'label' => 'Tipo', 'type' => 'text', 'sortable' => false],
                ['key' => 'supplier_name', 'label' => 'Suplidor', 'type' => 'text', 'sortable' => false],
                ['key' => 'supplier_rnc', 'label' => 'RNC suplidor', 'type' => 'text', 'sortable' => false],
                ['key' => 'total_amount', 'label' => 'Total', 'type' => 'currency', 'sortable' => false, 'align' => 'right'],
                ['key' => 'status', 'label' => 'Estado', 'type' => 'badge', 'sortable' => false, 'align' => 'right'],
                ['key' => 'actions', 'label' => 'Acciones', 'type' => 'actions', 'sortable' => false, 'align' => 'right'],
            ],
            'filters' => [
                [
                    'name' => 'search',
                    'label' => 'Buscar',
                    'type' => 'search',
                    'placeholder' => 'Buscar e-NCF, comprador, RNC o suplidor...',
                ],
                [
                    'name' => 'status',
                    'label' => 'Estado',
                    'type' => 'select',
                    'default' => 'all',
                    'inline' => true,
                    'options' => [
                        ['value' => 'received', 'label' => 'Recibido'],
                        ['value' => 'processed', 'label' => 'Procesado'],
                        ['value' => 'accepted', 'label' => 'Aceptado'],
                        ['value' => 'rejected', 'label' => 'Rechazado'],
                    ],
                ],
                [
                    'name' => 'supplier_id',
                    'label' => 'Suplidor',
                    'type' => 'select',
                    'default' => 'all',
                    'inline' => true,
                    'options' => $supplierOptions,
                ],
                [
                    'name' => 'from_start',
                    'label' => 'Fecha de recepción',
                    'type' => 'date',
                    'inline' => true,
                ],
                [
                    'name' => 'from_end',
                    'label' => 'Fecha de recepción',
                    'type' => 'date',
                    'inline' => true,
                ],
            ],
            'appliedFilters' => [
                'search' => (string) ($appliedFilters['search'] ?? ''),
                'status' => (string) ($appliedFilters['status'] ?? 'all'),
                'supplier_id' => (string) ($appliedFilters['supplier_id'] ?? 'all'),
                'from_start' => (string) ($appliedFilters['from'] ?? ''),
                'from_end' => (string) ($appliedFilters['to'] ?? ''),
            ],
            'sortBy' => null,
            'sortDirection' => 'desc',
            'perPage' => (int) ($pagination['per_page'] ?? 25),
            'perPageOptions' => [15, 25, 50, 100],
            'rowHref' => '/electronic-invoicing/received/{id}',
            'selectable' => false,
            'bulkActions' => [],
        ];
    }
}
