<?php

namespace Bernskiold\LaravelCollaboration\Contracts;

use Bernskiold\LaravelCollaboration\Enums\ShareOutcome;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Models\Share;
use Bernskiold\LaravelCollaboration\Support\ShareChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * The owner of a shareable record can share it with other people.
 *
 * The CanBeShared trait implements all the methods. Use the trait. Then
 * override shareOwnerColumn(), or override isOwnedBy() if the owner is not
 * in one column.
 */
interface Shareable
{
    /**
     * @return MorphMany<Share, covariant Model>
     */
    public function shares(): MorphMany;

    /**
     * This method returns true if the record is shared with the person, at
     * the permission or at a larger permission.
     */
    public function isSharedWith(Model $user, SharePermission $atLeast = SharePermission::View): bool;

    /**
     * This method returns the permission of the person, or null.
     */
    public function permissionFor(Model $user): ?SharePermission;

    /**
     * This method shares the record with one person. If the person has
     * access, the method changes the permission. The method returns Added,
     * Changed or Unchanged.
     */
    public function shareWith(Model $user, ?SharePermission $permission = null, ?Model $sharedBy = null): ShareOutcome;

    /**
     * This method removes the access of one person. The method returns
     * Removed or Unchanged.
     */
    public function stopSharingWith(Model $user, ?Model $performedBy = null): ShareOutcome;

    /**
     * This method sets the full list of people that have access to the
     * record.
     *
     * @param  Collection<int|string, SharePermission>|array<int|string, SharePermission>  $people
     */
    public function syncShares(Collection|array $people, ?Model $performedBy = null): ShareChanges;

    /**
     * This method returns the column that keeps the key of the owner, or
     * null if the record has no owner column.
     */
    public function shareOwnerColumn(): ?string;

    /**
     * This method returns true if the person is the owner of the record.
     * The package does not share a record with its owner. The owner is
     * not a collaborator.
     */
    public function isOwnedBy(Model $user): bool;

    /**
     * This method returns the permissions that the record offers, from the
     * smallest to the largest. The first permission is the default. If
     * there is only one permission, an interface does not ask for one.
     *
     * @return array<int, SharePermission>
     */
    public function sharePermissions(): array;

    /**
     * This method returns the name of the record. The notification and the
     * activity log use this name.
     */
    public function shareableName(): string;

    /**
     * This method returns the URL of the page of the record. The
     * notification has a link to this URL. Return null if the record has
     * no page.
     */
    public function shareableUrl(): ?string;

    /**
     * This method returns the notification for a person that gets access to
     * the record. Return null to send no notification.
     */
    public function shareNotification(Model $sharedWith, ?Model $sharedBy, SharePermission $permission): ?Notification;
}
