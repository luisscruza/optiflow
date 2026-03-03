<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre'),
                TextInput::make('email')->label('Correo electrónico'),
                TextInput::make('phone_primary')->label('Teléfono'),
            ]);
    }
}
