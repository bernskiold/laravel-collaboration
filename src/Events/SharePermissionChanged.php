<?php

namespace Bernskiold\LaravelCollaboration\Events;

use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The package dispatches this event when it changes the permission of a
 * person that has access to a record.
 *
 * The package dispatches the event after the transaction commits.
 */
class SharePermissionChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Model&Shareable $record,
        public readonly Model $sharedWith,
        public readonly ?Model $changedBy,
        public readonly SharePermission $from,
        public readonly SharePermission $to,
    ) {}
}
