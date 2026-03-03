<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Central\Client;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre'),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->live(onBlur: true)
                    ->hint(function (?string $state, ?Client $record): ?string {
                        if (blank($state)) {
                            return null;
                        }

                        $duplicate = Client::query()
                            ->select(['id', 'name'])
                            ->where('email', $state)
                            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                            ->first();

                        return $duplicate
                            ? "⚠️ Ya existe un cliente con este correo electrónico: {$duplicate->name}. Puede continuar de todas formas."
                            : null;
                    })
                    ->hintColor('warning'),
                TextInput::make('phone_primary')
                    ->label('Teléfono')
                    ->live(onBlur: true)
                    ->hint(function (?string $state, ?Client $record): ?string {
                        if (blank($state)) {
                            return null;
                        }

                        $duplicate = Client::query()
                            ->select(['id', 'name'])
                            ->where('phone_primary', $state)
                            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                            ->first();

                        return $duplicate
                            ? "⚠️ Ya existe un cliente con este teléfono: {$duplicate->name}. Puede continuar de todas formas."
                            : null;
                    })
                    ->hintColor('warning'),
            ]);
    }
}
