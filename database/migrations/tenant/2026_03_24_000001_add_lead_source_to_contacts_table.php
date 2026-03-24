<?php

declare(strict_types=1);

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreignId('lead_source_id')
                ->nullable()
                ->after('contact_type')
                ->constrained('mastertable_items')
                ->nullOnDelete();
        });

        $timestamp = now();

        DB::table('mastertables')->insert([
            'name' => 'Procedencia de contactos',
            'alias' => Contact::LEAD_SOURCES_MASTERTABLE_ALIAS,
            'description' => 'Fuentes de procedencia disponibles para contactos.',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $mastertableId = (int) DB::table('mastertables')
            ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
            ->value('id');

        DB::table('mastertable_items')->insert([
            [
                'mastertable_id' => $mastertableId,
                'name' => 'Redes sociales',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'mastertable_id' => $mastertableId,
                'name' => 'Google',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'mastertable_id' => $mastertableId,
                'name' => 'Publicidad',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'mastertable_id' => $mastertableId,
                'name' => 'Me refirio un amigo',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    public function down(): void
    {
        $mastertableId = DB::table('mastertables')
            ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
            ->value('id');

        if ($mastertableId !== null) {
            DB::table('mastertable_items')
                ->where('mastertable_id', $mastertableId)
                ->delete();

            DB::table('mastertables')
                ->where('id', $mastertableId)
                ->delete();
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lead_source_id');
        });
    }
};
