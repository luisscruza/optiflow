<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case StageChanged = 'stage_changed';
    case NoteAdded = 'note_added';
    case PriorityUpdated = 'priority_updated';
    case MetadataUpdated = 'metadata_updated';
    case ImagesAdded = 'images_added';
    case ImagesRemoved = 'images_removed';

    public function label(): string
    {
        return match ($this) {
            self::StageChanged => 'Cambio de etapa',
            self::NoteAdded => 'Nota agregada',
            self::PriorityUpdated => 'Cambio de prioridad',
            self::MetadataUpdated => 'Datos actualizados',
            self::ImagesAdded => 'Imágenes agregadas',
            self::ImagesRemoved => 'Imágenes eliminadas',
        };
    }
}
