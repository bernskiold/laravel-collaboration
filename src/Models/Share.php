<?php

namespace Bernskiold\LaravelCollaboration\Models;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A share gives one person access to one record.
 *
 * To change shares, use SyncShares or the methods of the CanBeShared trait.
 * They record the activity and dispatch the events. If you write to this
 * model directly, the package does not record or dispatch.
 *
 * @property int|string $shared_with_id
 * @property int|string|null $shared_by_id
 * @property SharePermission $permission
 * @property string $shareable_type
 * @property int|string $shareable_id
 * @property-read Model|null $sharedWith
 * @property-read Model|null $sharedBy
 * @property-read Model|null $shareable
 */
class Share extends Model
{
    protected $fillable = [
        'shared_with_id',
        'shared_by_id',
        'permission',
    ];

    public function getTable(): string
    {
        return config('collaboration.table', 'shares');
    }

    public function getConnectionName(): ?string
    {
        return config('collaboration.connection') ?? parent::getConnectionName();
    }

    protected function casts(): array
    {
        return [
            'permission' => SharePermission::class,
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(Collaboration::userModel(), 'shared_with_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(Collaboration::userModel(), 'shared_by_id');
    }

    /**
     * This scope finds the shares of a person, on all records.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function forPerson(Builder $query, Model|int|string $person): void
    {
        $query->where('shared_with_id', $person instanceof Model ? $person->getKey() : $person);
    }

    /**
     * This scope finds the shares at the permission or at a larger
     * permission.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function allowing(Builder $query, SharePermission $permission): void
    {
        $query->whereIn('permission', array_map(
            fn (SharePermission $case): string => $case->value,
            SharePermission::atLeast($permission),
        ));
    }
}
