<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case StageChanged = 'stage_changed';
    case NoteAdded = 'note_added';
}
