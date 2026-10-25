<?php

namespace Bernskiold\LaravelCollaboration\Events;

use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The package dispatches this event when a person gets access to a record.
 * A listener of the package sends the notification of the record.
 *
 * The package dispatches the event after the transaction commits.
 */
class RecordShared implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Model&Shareable $record,
        public readonly Model $sharedWith,
        public readonly ?Model $sharedBy,
        public readonly SharePermission $permission,
    ) {}
}
