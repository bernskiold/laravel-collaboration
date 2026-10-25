<?php

namespace Bernskiold\LaravelCollaboration\Concerns;

use Bernskiold\LaravelCollaboration\Actions\SyncShares;
use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Models\Share;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * This trait implements the Shareable contract, but not isOwnedBy().
 *
 * When you delete the record, the trait also deletes its shares. A
 * soft-deleted record keeps its shares until you force-delete it. When you
 * restore the record, it has the same collaborators.
 *
 * @mixin Model
 *
 * @phpstan-require-implements Shareable
 */
trait CanBeShared
{
    public static function bootCanBeShared(): void
    {
        $event = in_array(SoftDeletes::class, class_uses_recursive(static::class), true)
            ? 'forceDeleted'
            : 'deleted';

        static::registerModelEvent($event, function (Model $record): void {
            /** @var Model&Shareable $record */
            $record->shares()->delete();
        });
    }

    /**
     * @return MorphMany<Share, $this>
     */
    public function shares(): MorphMany
    {
        return $this->morphMany(Collaboration::shareModel(), 'shareable');
    }

    /**
     * This scope finds the records that are shared with the person, at the
     * permission or at a larger permission.
     *
     * The query uses whereHas(). For this reason, the shares table must be
     * on the same database server as the table of the record.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function sharedWith(Builder $query, Model|int|string $user, SharePermission $atLeast = SharePermission::View): void
    {
        $query->whereHas('shares', function (Builder $shares) use ($user, $atLeast): void {
            /** @var Builder<Share> $shares */
            $shares->forPerson($user)->allowing($atLeast);
        });
    }

    /**
     * This method returns true if the record is shared with the person, at
     * the permission or at a larger permission. The owner is not a
     * collaborator. For the owner, the method returns false.
     */
    public function isSharedWith(Model $user, SharePermission $atLeast = SharePermission::View): bool
    {
        return $this->permissionFor($user)?->allows($atLeast) ?? false;
    }

    /**
     * This method returns the permission of the person, or null. If the
     * shares are loaded, the method does not query the database.
     */
    public function permissionFor(Model $user): ?SharePermission
    {
        $share = $this->relationLoaded('shares')
            ? $this->getRelation('shares')->firstWhere('shared_with_id', $user->getKey())
            : $this->shares()->where('shared_with_id', $user->getKey())->first();

        return $share?->permission;
    }

    /**
     * This method shares the record with one person. If the person has
     * access, the method changes the permission. The other shares do not
     * change.
     */
    public function shareWith(Model $user, ?SharePermission $permission = null, ?Model $sharedBy = null): void
    {
        $this->syncShares(
            $this->currentShares()->put($user->getKey(), $permission ?? $this->sharePermissions()[0]),
            $sharedBy ?? Collaboration::actingUser(),
        );
    }

    /**
     * This method removes the access of one person. The other shares do not
     * change.
     */
    public function stopSharingWith(Model $user, ?Model $performedBy = null): void
    {
        $this->syncShares(
            $this->currentShares()->forget($user->getKey()),
            $performedBy ?? Collaboration::actingUser(),
        );
    }

    /**
     * This method sets the full list of people that have access to the
     * record.
     *
     * @param  Collection<int|string, SharePermission>|array<int|string, SharePermission>  $people  The permissions. The key of each item is the key of a person.
     * @return Collection<int, Share>
     */
    public function syncShares(Collection|array $people, ?Model $performedBy = null): Collection
    {
        /** @var Model&Shareable $this */
        return app(SyncShares::class)->execute($this, collect($people), $performedBy);
    }

    /**
     * @return array<int, SharePermission>
     */
    public function sharePermissions(): array
    {
        return SharePermission::cases();
    }

    public function shareableName(): string
    {
        return (string) $this->getAttribute('name');
    }

    public function shareableUrl(): ?string
    {
        return null;
    }

    public function shareNotification(Model $sharedWith, ?Model $sharedBy, SharePermission $permission): ?Notification
    {
        $notification = config('collaboration.notifications.default');

        if ($notification === null) {
            return null;
        }

        return new $notification($this, $sharedBy, $permission);
    }

    /**
     * @return Collection<int|string, SharePermission>
     */
    protected function currentShares(): Collection
    {
        return $this->shares()
            ->get()
            ->mapWithKeys(fn (Share $share): array => [$share->shared_with_id => $share->permission])
            ->toBase();
    }
}
