<?php

namespace Bernskiold\LaravelCollaboration\Events;

use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The package dispatches this event when it removes the access of a person
 * to a record.
 *
 * The package does not dispatch the event when it deletes the shares
 * together with the record. HandOverShares also does not dispatch it.
 *
 * The package dispatches the event after the transaction commits.
 */
class RecordUnshared implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Model&Shareable $record,
        public readonly Model $sharedWith,
        public readonly ?Model $removedBy,
        public readonly SharePermission $permission,
    ) {}
}
