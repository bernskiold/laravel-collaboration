<?php

namespace Bernskiold\LaravelCollaboration\Actions;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Models\Share;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * This action moves the shares of one person to a second person. Use it
 * when you merge two user accounts.
 *
 * - If only the first person had access to a record, the second person
 *   gets the same access.
 * - If the two people had access, the second person keeps the larger
 *   permission.
 * - If the second person is the owner of a record, the action deletes the
 *   share on that record.
 *
 * The person that shared each record does not change. The action records
 * no activity and dispatches no events. The access of each person stays
 * the same.
 */
class HandOverShares
{
    public function execute(Model $from, Model $to): void
    {
        $shareModel = Collaboration::shareModel();

        DB::connection((new $shareModel)->getConnectionName())->transaction(function () use ($shareModel, $from, $to): void {
            $shareModel::query()
                ->where('shared_with_id', $from->getKey())
                ->with('shareable')
                ->get()
                ->each(fn (Share $share) => $this->moveOrMerge($share, $to));

            $shareModel::query()
                ->where('shared_with_id', $to->getKey())
                ->with('shareable')
                ->get()
                ->filter(fn (Share $share): bool => $this->isOwnedBy($share, $to))
                ->each(fn (Share $share): ?bool => $share->delete());
        });
    }

    protected function moveOrMerge(Share $share, Model $to): void
    {
        if ($this->isOwnedBy($share, $to)) {
            $share->delete();

            return;
        }

        $theirs = $share->newQuery()
            ->where('shareable_type', $share->shareable_type)
            ->where('shareable_id', $share->shareable_id)
            ->where('shared_with_id', $to->getKey())
            ->first();

        if ($theirs === null) {
            $share->update(['shared_with_id' => $to->getKey()]);

            return;
        }

        if (! $theirs->permission->allows($share->permission)) {
            $theirs->update(['permission' => $share->permission]);
        }

        $share->delete();
    }

    protected function isOwnedBy(Share $share, Model $user): bool
    {
        $record = $share->shareable;

        return $record instanceof Shareable && $record->isOwnedBy($user);
    }
}
