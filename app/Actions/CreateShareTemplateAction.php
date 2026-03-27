<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ShareTemplate;

final class CreateShareTemplateAction
{
    /**
     * @param  array{entity_type: mixed, channel: mixed, name: string, subject: ?string, body: string, is_active: bool}  $data
     */
    public function handle(array $data): ShareTemplate
    {
        $shareTemplate = new ShareTemplate();
        $shareTemplate->forceFill($data);
        $shareTemplate->save();

        return $shareTemplate;
    }
}
