<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

final class TenantAwareUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        if ($this->getDiskName() === 'r2') {
            $url = $this->getDisk()->url($this->getPathRelativeToRoot());

            return $this->versionUrl($url);
        }

        $url = asset($this->getPathRelativeToRoot());

        $url = $this->versionUrl($url);

        return $url;
    }
}
