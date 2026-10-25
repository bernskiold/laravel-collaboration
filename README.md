# Share Eloquent records with other users

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bernskiold/laravel-collaboration.svg?style=flat-square)](https://packagist.org/packages/bernskiold/laravel-collaboration)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/bernskiold/laravel-collaboration/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/bernskiold/laravel-collaboration/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/bernskiold/laravel-collaboration/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/bernskiold/laravel-collaboration/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

The owner of a record can share it with other users. Each user gets a permission: "view" or "edit". The policy of the record controls what each permission lets a user do.

The package has no user interface. It has no views and no components. It gives the data, the rules, the events, the notification and the activity log. Your application has the interface.

```php
$portfolio->shareWith($ada, SharePermission::Edit);

$portfolio->isSharedWith($ada);                          // true
$portfolio->isSharedWith($ada, SharePermission::Edit);   // true
Portfolio::query()->sharedWith($ada)->get();

$portfolio->stopSharingWith($ada);
```

## What the package does

- It keeps the shares in one table. The table has one row for each user and record.
- It gives the `CanBeShared` trait for your models. The trait has a scope, permission checks and methods that change the shares.
- It writes all changes with one action, `SyncShares`. This makes sure that each change has the same rules, the same events and the same activity entries.
- It sends a notification to each user that gets access. Each type of record can use a different notification, or no notification.
- It records each change in the activity log of the record, if `spatie/laravel-activitylog` is installed.
- It moves the shares of a user to a different user. Use this function when you merge two accounts.
- It gives helpers for your interface. It finds a record from a morph type and a key. It finds the people that can get access to a record.

## Installation

Install the package with Composer:

```bash
composer require bernskiold/laravel-collaboration
```

Publish the migration:

```bash
php artisan vendor:publish --tag=collaboration-migrations
```

Run the migration:

```bash
php artisan migrate
```

If your application enforces a morph map, add the share model to it:

```php
Relation::enforceMorphMap([
    'share' => \Bernskiold\LaravelCollaboration\Models\Share::class,
    // ...
]);
```

## Make a model shareable

Implement `Shareable`. Use `CanBeShared`. Then write the `isOwnedBy()` method:

```php
use Bernskiold\LaravelCollaboration\Concerns\CanBeShared;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;

class Portfolio extends Model implements Shareable
{
    use CanBeShared;

    public function isOwnedBy(Model $user): bool
    {
        return $this->owner_id === $user->getKey();
    }
}
```

The package does not share a record with its owner. When you delete a record, the package deletes its shares. A soft-deleted record keeps its shares until you force-delete it.

You can override these methods of the trait:

| Method                | Default                                              |
|-----------------------|------------------------------------------------------|
| `sharePermissions()`  | View and edit. The first permission is the default.  |
| `shareableName()`     | The `name` attribute.                                |
| `shareableUrl()`      | `null`. The notification has a link to this URL.     |
| `shareNotification()` | The notification in the configuration.               |

If `sharePermissions()` returns one permission, your interface does not ask for a permission:

```php
public function sharePermissions(): array
{
    return [SharePermission::View];
}
```

## Use the permissions in a policy

The package does not give access. Your policy reads the shares:

```php
class PortfolioPolicy
{
    public function view(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->isOwnedBy($user) || $portfolio->isSharedWith($user);
    }

    public function update(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->isOwnedBy($user) || $portfolio->isSharedWith($user, SharePermission::Edit);
    }

    public function share(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->isOwnedBy($user);
    }
}
```

If you eager load the shares, `isSharedWith()` and `permissionFor()` use the loaded shares. Then a list does not make one query for each record:

```php
Portfolio::query()->with('shares')->get()->filter(fn ($portfolio) => $user->can('view', $portfolio));
```

The `sharedWith()` scope uses `whereHas()`. For this reason, the shares table must be on the same database server as the table of the record.

## Change the shares

```php
// One person. The other shares do not change.
$record->shareWith($user, SharePermission::View, $sharedBy);
$record->stopSharingWith($user, $removedBy);

// The full list. The package removes the access of each person that is not in the list.
$record->syncShares([
    $ada->id => SharePermission::Edit,
    $alan->id => SharePermission::View,
], $performedBy);
```

The person that makes the change is optional. If you do not give this person, the package uses the signed-in user.

A permission that the record does not offer causes an `InvalidArgumentException`. The package does not share a record with its owner or with an unknown user.

### Events

| Event                    | The package dispatches the event when…            |
|--------------------------|---------------------------------------------------|
| `RecordShared`           | A user gets access.                               |
| `SharePermissionChanged` | A user with access gets a different permission.   |
| `RecordUnshared`         | The package removes the access of a user.         |

The package dispatches the events after the transaction commits. The events are in the `Bernskiold\LaravelCollaboration\Events` namespace.

Use the events to refresh your interface. You can also use them to clear caches that use the access of a user.

## Notifications

When a user gets access, the package sends the notification from the `shareNotification()` method of the record. The default notification is `SharedWithYou`. It is a queued email with a link to `shareableUrl()`.

To send a different notification for one type of record, override the method. Return `null` to send no notification:

```php
public function shareNotification(Model $sharedWith, ?Model $sharedBy, SharePermission $permission): ?Notification
{
    return new PortfolioShared($this, $sharedBy);
}
```

The text of the notification is in `lang/en/collaboration.php`. To change the text, publish the translations:

```bash
php artisan vendor:publish --tag=collaboration-translations
```

To change the notification for all records, set `notifications.default` in the configuration. The constructor gets the record, the user that shared the record and the permission. To send no notifications, set `notifications.enabled` to `false`.

## Activity log

If `spatie/laravel-activitylog` is installed, the package records the changes in the activity log of the record. Each call to `SyncShares` makes one entry for each type of change. The events of the entries are `shared`, `share-changed` and `unshared`. The `people` property has the names of the users. The `people_ids` property has their keys.

`ShareActivity::describe($entry)` returns a description of an entry, as a verb and an object:

```php
ShareActivity::describe($entry); // ['verb' => 'shared it with', 'object' => 'Ada Lovelace, Alan Turing']
```

## Make your interface

The package has no interface. These helpers keep your components small.

### Find the record

A component or an API tool can get the record as a morph type and a key. `findShareable()` returns the record. It returns `null` if the type is not a shareable model or if the record does not exist. Then authorize the user:

```php
use Bernskiold\LaravelCollaboration\Collaboration;

$record = Collaboration::findShareable($this->type, $this->key);

abort_if($record === null, 404);

$this->authorize('share', $record);
```

The type can be a morph alias or a class name. In a Livewire component, add `#[Locked]` to the type and the key. Then the browser cannot change the record after you authorize it.

### Find people

`PeopleDirectory` searches all users by name and email address. The results do not include the owner of the record:

```php
$people = Collaboration::people()->search($term, $record, limit: 15);
```

`resolve()` returns the person for the value that the user selected. The value is a key, or an email address to invite. If the person does not exist, `resolve()` returns `null`:

```php
$person = Collaboration::people()->resolve($this->person);

if ($person === null || $record->isOwnedBy($person)) {
    $this->addError('person', __('Select a person.'));

    return;
}

$record->shareWith($person, SharePermission::from($this->permission));
```

To change the search, extend `PeopleDirectory`. Then set your class in the `people` configuration:

```php
class ActivePeople extends PeopleDirectory
{
    protected function query(): Builder
    {
        return User::query()->active();
    }
}
```

Users can also share a record with an email address that has no account. To let them do this, override `canInvite()` and `invite()`. If the address has an account, `invite()` returns that user:

```php
public function canInvite(): bool
{
    return true;
}

public function invite(string $email): ?Model
{
    return User::query()->firstOrCreate(['email' => $email], [/* ... */]);
}
```

`name()` returns the name of a person. The activity log and the notification use this name.

### Show the permissions

`SharePermission` has a label and a description from the translations of the package:

```php
foreach ($record->sharePermissions() as $permission) {
    $permission->value;          // "view"
    $permission->label();        // "Can view"
    $permission->description();  // "Can open it, but cannot change it."
}
```

## Merge two users

`HandOverShares` moves the shares of one user to a second user. If the two users had access to a record, the second user keeps the larger permission. If the second user is the owner of a record, the package deletes the share on that record.

```php
app(HandOverShares::class)->execute(from: $duplicate, to: $user);
```

Some merge tools move all the relations of a user. If you use such a tool, do not add a `shares` relation to your user model. A direct update can break the unique index of the table.

## Configuration

To change the configuration, publish the configuration file:

```bash
php artisan vendor:publish --tag=collaboration-config
```

```php
return [
    'user_model' => null,                     // null: the model of the default auth provider
    'share_model' => Share::class,
    'table' => 'shares',
    'connection' => null,                     // null: the default connection
    'people' => PeopleDirectory::class,
    'notifications' => ['enabled' => true, 'default' => SharedWithYou::class],
    'activity_log' => ['enabled' => true, 'log_name' => null], // null: the morph class of the record
];
```

## Testing

```bash
composer test
```

## Changelog

Refer to the [CHANGELOG](CHANGELOG.md) for the recent changes.

## Contributing

Refer to [CONTRIBUTING](CONTRIBUTING.md) for the procedure.

## Security Vulnerabilities

Refer to [our security policy](SECURITY.md) for how to report a security vulnerability.

## Credits

- [Erik Bernskiöld](https://bernskiold.com)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Refer to the [License File](LICENSE.md) for more information.
