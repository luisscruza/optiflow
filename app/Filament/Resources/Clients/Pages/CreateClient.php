<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;
}
