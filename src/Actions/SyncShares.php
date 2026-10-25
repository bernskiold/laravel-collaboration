<?php

namespace Bernskiold\LaravelCollaboration\Actions;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Events\RecordShared;
use Bernskiold\LaravelCollaboration\Events\RecordUnshared;
use Bernskiold\LaravelCollaboration\Events\SharePermissionChanged;
use Bernskiold\LaravelCollaboration\Models\Share;
use Bernskiold\LaravelCollaboration\Support\ShareActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * This action sets the full list of people that have access to a record.
 *
 * This is the only class that writes shares. This makes sure that each
 * change has the same rules, the same activity entries and the same events:
 *
 * - The action removes the access of a person that is not in the list,
 *   and dispatches RecordUnshared.
 * - The action gives access to a person that is new in the list, and
 *   dispatches RecordShared.
 * - The action changes the permission of a person, if the permission in
 *   the list is different. Then it dispatches SharePermissionChanged.
 *
 * The action does not share a record with its owner or with an unknown
 * person. A permission that the record does not offer causes an exception.
 */
class SyncShares
{
    public function __construct(
        protected ShareActivity $activity,
    ) {}

    /**
     * @param  Collection<int|string, SharePermission|string>  $people  The permissions. The key of each item is the key of a person.
     * @return Collection<int, Share>
     */
    public function execute(Model&Shareable $record, Collection $people, ?Model $performedBy = null): Collection
    {
        $people = $people->map(
            fn (SharePermission|string $permission): SharePermission => $permission instanceof SharePermission ? $permission : SharePermission::from($permission)
        );

        $users = Collaboration::userQueryWithTrashed()
            ->whereKey($people->keys()->all())
            ->get()
            ->keyBy(fn (Model $user): int|string => $user->getKey());

        $people = $people->filter(
            fn (SharePermission $permission, int|string $key): bool => $users->has($key) && ! $record->isOwnedBy($users->get($key))
        );

        return DB::connection($record->shares()->getRelated()->getConnectionName())
            ->transaction(function () use ($record, $people, $users, $performedBy): Collection {
                $existing = $record->shares()->get()->keyBy('shared_with_id')->toBase();
                $previous = $existing->map(fn (Share $share): SharePermission => $share->permission);

                $this->ensureOffered($record, $people->reject(
                    fn (SharePermission $permission, int|string $key): bool => $previous->get($key) === $permission
                ));

                $added = $people->diffKeys($existing);
                $removed = $previous->diffKeys($people);
                $changed = $people->intersectByKeys($previous)->filter(
                    fn (SharePermission $permission, int|string $key): bool => $previous->get($key) !== $permission
                );

                foreach ($added as $key => $permission) {
                    $record->shares()->create([
                        'shared_with_id' => $key,
                        'shared_by_id' => $performedBy?->getKey(),
                        'permission' => $permission,
                    ]);
                }

                foreach ($changed as $key => $permission) {
                    $existing->get($key)->update(['permission' => $permission]);
                }

                if ($removed->isNotEmpty()) {
                    $record->shares()->whereIn('shared_with_id', $removed->keys()->all())->delete();
                }

                $removedUsers = Collaboration::userQueryWithTrashed()
                    ->whereKey($removed->keys()->all())
                    ->get();

                $this->activity->record($record, ShareActivity::SHARED, $users->only($added->keys()->all())->values(), $performedBy);
                $this->activity->record($record, ShareActivity::CHANGED, $users->only($changed->keys()->all())->values(), $performedBy);
                $this->activity->record($record, ShareActivity::UNSHARED, $removedUsers, $performedBy);

                foreach ($added as $key => $permission) {
                    RecordShared::dispatch($record, $users->get($key), $performedBy, $permission);
                }

                foreach ($changed as $key => $permission) {
                    SharePermissionChanged::dispatch($record, $users->get($key), $performedBy, $previous->get($key), $permission);
                }

                foreach ($removedUsers as $user) {
                    RecordUnshared::dispatch($record, $user, $performedBy, $removed->get($user->getKey()));
                }

                return $record->shares()->with('sharedWith')->get()->toBase();
            });
    }

    /**
     * This method causes an exception if the record does not offer one of
     * the permissions. The method examines only the new and the changed
     * permissions. If a record stops to offer a permission, the existing
     * shares at that permission stay valid.
     *
     * @param  Collection<int|string, SharePermission>  $people
     */
    protected function ensureOffered(Model&Shareable $record, Collection $people): void
    {
        $offered = $record->sharePermissions();

        foreach ($people as $permission) {
            if (! in_array($permission, $offered, true)) {
                throw new InvalidArgumentException(sprintf(
                    'You cannot share a %s with the "%s" permission.',
                    $record::class,
                    $permission->value,
                ));
            }
        }
    }
}
