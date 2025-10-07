<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Prescription extends Model
{
    use BelongsToWorkspace;

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'patient_id');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function optometrist(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'optometrist_id');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function motivos(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'motivos_consulta')
            ->withPivot('mastertable_alias');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function estadoActual(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'estado_salud_actual')
            ->withPivot('mastertable_alias');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function historiaOcularFamiliar(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'historia_ocular_familiar')
            ->withPivot('mastertable_alias');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function lentesRecomendados(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'tipos_de_lentes')
            ->withPivot('mastertable_alias');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function gotasRecomendadas(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'tipos_de_gotas')
            ->withPivot('mastertable_alias');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function monturasRecomendadas(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'tipos_de_montura')
            ->withPivot('mastertable_alias');
    }

    /**
     * @return BelongsToMany<MastertableItem>
     */
    public function canalesDeReferimiento(): BelongsToMany
    {
        return $this->belongsToMany(MastertableItem::class, 'prescription_item', 'prescription_id', 'mastertable_item_id')
            ->wherePivot('mastertable_alias', 'canales_de_referimiento')
            ->withPivot('mastertable_alias');
    }
}
