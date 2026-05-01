<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Models\Workspace;
use App\Reports\InvoicesVsExpensesReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable();
        $table->timestamps();
    });

    Schema::create('document_subtypes', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
        $table->boolean('is_active')->default(true);
        $table->boolean('is_default')->default(false);
        $table->boolean('is_electronic')->default(false);
        $table->date('valid_until_date')->nullable();
        $table->integer('start_number')->default(1);
        $table->integer('end_number')->nullable();
        $table->integer('next_number')->default(1);
        $table->timestamps();
    });

    Schema::create('invoices', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id');
        $table->unsignedBigInteger('document_subtype_id');
        $table->string('status');
        $table->date('issue_date');
        $table->decimal('subtotal_amount', 12, 2)->default(0);
        $table->decimal('tax_amount', 12, 2)->default(0);
        $table->decimal('total_amount', 12, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('expenses', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('workspace_id');
        $table->boolean('is_informal')->default(false);
        $table->string('status');
        $table->date('issue_date');
        $table->decimal('subtotal_amount', 12, 2)->default(0);
        $table->decimal('itbis_amount', 12, 2)->default(0);
        $table->timestamps();
    });
});

it('summarizes invoices vs expenses globally and by workspace', function (): void {
    $north = Workspace::query()->create(['id' => 1, 'name' => 'Norte']);
    $south = Workspace::query()->create(['id' => 2, 'name' => 'Sur']);

    Auth::shouldReceive('user')->andReturn(new class($north, $south)
    {
        public mixed $workspaces;

        public function __construct(public Workspace $north, public Workspace $south)
        {
            $this->workspaces = collect([$north, $south]);
        }

        public function can(mixed $permission): bool
        {
            return $permission === App\Enums\Permission::ViewAllLocations || $permission === 'view all locations';
        }

        public function workspaces()
        {
            return Workspace::query()->whereIn('id', [1, 2]);
        }
    });

    DB::table('document_subtypes')->insert([
        ['id' => 1, 'name' => 'Crédito fiscal', 'type' => DocumentType::Invoice->value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Consumo', 'type' => DocumentType::Invoice->value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('invoices')->insert([
        ['workspace_id' => 1, 'document_subtype_id' => 1, 'status' => 'paid', 'issue_date' => '2026-05-01', 'subtotal_amount' => 1000, 'tax_amount' => 180, 'total_amount' => 1180, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => 2, 'document_subtype_id' => 2, 'status' => 'pending_payment', 'issue_date' => '2026-05-02', 'subtotal_amount' => 500, 'tax_amount' => 90, 'total_amount' => 590, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => 1, 'document_subtype_id' => 1, 'status' => 'cancelled', 'issue_date' => '2026-05-03', 'subtotal_amount' => 999, 'tax_amount' => 179.82, 'total_amount' => 1178.82, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('expenses')->insert([
        ['workspace_id' => 1, 'is_informal' => false, 'status' => 'pending', 'issue_date' => '2026-05-04', 'subtotal_amount' => 300, 'itbis_amount' => 54, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => 2, 'is_informal' => true, 'status' => 'paid', 'issue_date' => '2026-05-05', 'subtotal_amount' => 200, 'itbis_amount' => 36, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => 1, 'is_informal' => false, 'status' => 'cancelled', 'issue_date' => '2026-05-06', 'subtotal_amount' => 100, 'itbis_amount' => 18, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $report = new InvoicesVsExpensesReport;

    $summary = collect($report->summary([
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'document_subtype_id' => '1,2',
        'is_informal' => 'all',
    ]))->keyBy('key');

    $rows = $report->data([
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'document_subtype_id' => '1,2',
        'is_informal' => 'all',
    ]);

    expect($summary['invoice_subtotal']['value'])->toBe(1500.0)
        ->and($summary['expense_subtotal']['value'])->toBe(500.0)
        ->and($summary['invoice_itbis']['value'])->toBe(270.0)
        ->and($summary['expense_itbis']['value'])->toBe(90.0)
        ->and($summary['net_itbis']['value'])->toBe(180.0)
        ->and($rows)->toHaveCount(3)
        ->and($rows[0]['scope_label'])->toBe('General')
        ->and($rows[1]['scope_label'])->toBe('Norte')
        ->and($rows[2]['scope_label'])->toBe('Sur');
});

it('filters by document subtype and expense formality', function (): void {
    $north = Workspace::query()->create(['id' => 1, 'name' => 'Norte']);

    Auth::shouldReceive('user')->andReturn(new class($north)
    {
        public mixed $workspaces;

        public function __construct(public Workspace $north)
        {
            $this->workspaces = collect([$north]);
        }

        public function can(mixed $permission): bool
        {
            return false;
        }

        public function workspaces()
        {
            return Workspace::query()->where('id', 1);
        }
    });

    DB::table('document_subtypes')->insert([
        ['id' => 1, 'name' => 'Crédito fiscal', 'type' => DocumentType::Invoice->value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Consumo', 'type' => DocumentType::Invoice->value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('invoices')->insert([
        ['workspace_id' => 1, 'document_subtype_id' => 1, 'status' => 'paid', 'issue_date' => '2026-05-01', 'subtotal_amount' => 1000, 'tax_amount' => 180, 'total_amount' => 1180, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => 1, 'document_subtype_id' => 2, 'status' => 'paid', 'issue_date' => '2026-05-02', 'subtotal_amount' => 500, 'tax_amount' => 90, 'total_amount' => 590, 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('expenses')->insert([
        ['workspace_id' => 1, 'is_informal' => false, 'status' => 'pending', 'issue_date' => '2026-05-04', 'subtotal_amount' => 300, 'itbis_amount' => 54, 'created_at' => now(), 'updated_at' => now()],
        ['workspace_id' => 1, 'is_informal' => true, 'status' => 'pending', 'issue_date' => '2026-05-05', 'subtotal_amount' => 200, 'itbis_amount' => 36, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $report = new InvoicesVsExpensesReport;

    $summary = collect($report->summary([
        'workspace_id' => '1',
        'document_subtype_id' => '1',
        'is_informal' => '0',
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
    ]))->keyBy('key');

    expect($summary['invoice_subtotal']['value'])->toBe(1000.0)
        ->and($summary['expense_subtotal']['value'])->toBe(300.0)
        ->and($summary['invoice_itbis']['value'])->toBe(180.0)
        ->and($summary['expense_itbis']['value'])->toBe(54.0)
        ->and($summary['net_itbis']['value'])->toBe(126.0);
});
