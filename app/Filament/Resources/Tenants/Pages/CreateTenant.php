<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
