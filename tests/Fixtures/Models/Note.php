<?php

namespace Bernskiold\LaravelCollaboration\Tests\Fixtures\Models;

use Bernskiold\LaravelCollaboration\Concerns\CanBeShared;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notification;

/**
 * A soft-deletable record. It offers only the view permission, and it
 * sends no notification.
 */
class Note extends Model implements Shareable
{
    use CanBeShared;
    use SoftDeletes;

    protected $guarded = [];

    public function isOwnedBy(Model $user): bool
    {
        return $this->owner_id !== null && $this->owner_id === $user->getKey();
    }

    public function sharePermissions(): array
    {
        return [SharePermission::View];
    }

    public function shareNotification(Model $sharedWith, ?Model $sharedBy, SharePermission $permission): ?Notification
    {
        return null;
    }
}
