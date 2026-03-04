<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Models\DocumentSubtype;
use App\Models\Workspace;
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
        Schema::table('document_subtypes', function (Blueprint $table): void {
            $table->string('prefix', 10)->change();
        });

        $workspaces = Workspace::query()->select(['id', 'name', 'slug'])->get();

        foreach ($workspaces as $workspace) {
            $seed = strtoupper((string) ($workspace->slug ?: $workspace->name));
            $seed = preg_replace('/[^A-Z0-9]/', '', $seed) ?: 'WS';
            $workspaceCode = substr($seed, 0, 2);
            $basePrefix = 'RPS'.$workspaceCode;

            $prefix = $basePrefix;
            $suffix = 1;
            while (DocumentSubtype::query()->where('prefix', $prefix)->exists()) {
                $prefix = substr($basePrefix, 0, 8).$suffix;
                $suffix++;
            }

            $subtype = DocumentSubtype::query()->create([
                'name' => 'Recibo de pago - '.$workspace->name,
                'type' => DocumentType::Payment->value,
                'is_default' => false,
                'valid_until_date' => null,
                'prefix' => $prefix,
                'start_number' => 1,
                'end_number' => null,
                'next_number' => 1,
            ]);

            $workspace->documentSubtypes()->syncWithoutDetaching([
                $subtype->id => ['is_preferred' => false],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $paymentSubtypes = DocumentSubtype::query()
            ->where('type', DocumentType::Payment->value)
            ->where('name', 'like', 'Recibo de pago - %')
            ->get();

        foreach ($paymentSubtypes as $subtype) {
            $subtype->workspaces()->detach();
            $subtype->delete();
        }

        Schema::table('document_subtypes', function (Blueprint $table): void {
            $table->string('prefix', 3)->change();
        });
    }
};
