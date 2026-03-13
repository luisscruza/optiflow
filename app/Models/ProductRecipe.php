<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $created_by
 * @property int $contact_id
 * @property int $optometrist_id
 * @property int $product_id
 * @property string|null $indication
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Contact $contact
 * @property-read User $creator
 * @property-read Contact $optometrist
 * @property-read MastertableItem $product
 * @property-read Workspace $workspace
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRecipe forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRecipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRecipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRecipe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductRecipe withoutWorkspaceScope()
 *
 * @mixin \Eloquent
 */
final class ProductRecipe extends Model
{
    /** @use HasFactory<\Database\Factories\ProductRecipeFactory> */
    use BelongsToWorkspace, HasFactory;

    public const PRODUCTS_MASTERTABLE_ALIAS = 'productos_recetarios';

    protected $fillable = [
        'workspace_id',
        'created_by',
        'contact_id',
        'optometrist_id',
        'product_id',
        'indication',
    ];

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function optometrist(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'optometrist_id');
    }

    /**
     * @return BelongsTo<MastertableItem, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MastertableItem::class, 'product_id');
    }
}
