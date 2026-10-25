<?php

namespace Bernskiold\LaravelCollaboration\Tests\Fixtures;

use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Illuminate\Database\Eloquent\Model;

/**
 * The owner can view and share. A collaborator can view.
 */
class RecordPolicy
{
    public function view(Model $user, Model&Shareable $record): bool
    {
        return $record->isOwnedBy($user) || $record->isSharedWith($user);
    }

    public function share(Model $user, Model&Shareable $record): bool
    {
        return $record->isOwnedBy($user);
    }
}
