<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\CompanyDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCompanyDetailsAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?UploadedFile $logo): void
    {
        DB::transaction(function () use ($data, $logo): void {
            if ($logo instanceof UploadedFile) {
                if (! $logo->isValid()) {
                    throw new ActionValidationException([
                        'logo' => 'El logo subido no es válido.',
                    ]);
                }

                $data['logo'] = $logo->store('company-logos', 'public');
            } else {
                unset($data['logo']);
            }

            foreach ($data as $key => $value) {
                CompanyDetail::setByKey($key, $value ?? '');
            }
        });
    }
}
