# Changelog

This file records all the important changes to `laravel-collaboration`.

## Unreleased

- First release.
- The package has no user interface. It has no views and no components.
- The `CanBeShared` trait and the `Shareable` contract make a model shareable. The trait gives the `sharedWith()` scope, `isSharedWith()`, `permissionFor()`, `shareWith()`, `stopSharingWith()` and `syncShares()`.
- The `SharePermission` enum has the "view" and "edit" permissions. Edit includes view.
- The `SyncShares` action writes all the changes to shares. It dispatches `RecordShared`, `SharePermissionChanged` and `RecordUnshared` after the transaction commits.
- The package sends a notification to each user that gets access. Each record selects its notification, or no notification.
- If `spatie/laravel-activitylog` is installed, the package records each change in the activity log of the record.
- The `HandOverShares` action moves the shares of a user to a different user.
- `Collaboration::findShareable()` finds a shareable record from a morph type and a key. It can also find the record by a different field.
- `shareOwnerColumn()` gives the owner column. `isOwnedBy()`, the `ownedBy()` scope and the `ownedByOrSharedWith()` scope use it.
- The `sharedWith()`, `ownedBy()` and `ownedByOrSharedWith()` scopes accept `null`. Then they find no records.
- `shareWith()` and `stopSharingWith()` return a `ShareOutcome`. `syncShares()` and `SyncShares` return `ShareChanges`.
- `SyncShares` examines only the new and the changed permissions. If a record stops to offer a permission, its existing shares stay valid.
- `Share::factory()` makes shares for tests.
- The `PeopleDirectory` class finds the users that can get access to a record. It also finds the user that a person picker selected. It can invite email addresses.
