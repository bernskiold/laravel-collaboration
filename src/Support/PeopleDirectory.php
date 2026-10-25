<?php

namespace Bernskiold\LaravelCollaboration\Support;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * This class finds the people that can get access to a record, and gives
 * their names. Use it for the person picker of your interface. Also use it
 * to find the person that the user selected.
 *
 * The default class searches all users by name and email address. To
 * change the search, extend this class. Then set your class in the
 * "people" configuration. This example shows only active users:
 *
 *     protected function query(): Builder
 *     {
 *         return User::query()->active();
 *     }
 *
 * To let users share a record with an email address that has no account,
 * override canInvite() and invite().
 */
class PeopleDirectory
{
    /**
     * This method returns a query for the people that can get access to a
     * record.
     *
     * @return Builder<Model>
     */
    protected function query(): Builder
    {
        $model = Collaboration::userModel();

        return $model::query();
    }

    /**
     * This method returns the columns that the search compares with the
     * search term.
     *
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return ['name', 'email'];
    }

    /**
     * This method returns the people that the search term finds. The
     * results do not include the owner of the record.
     *
     * @return Collection<int, Model>
     */
    public function search(string $term, Model&Shareable $record, int $limit): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return collect();
        }

        $columns = $this->searchColumns();

        $query = $this->query()->where(function (Builder $query) use ($columns, $term): void {
            foreach ($columns as $column) {
                $query->orWhereLike($column, '%'.$term.'%');
            }
        });

        $query->orderBy($columns[0])->limit($limit);

        return $query->get()
            ->reject(fn (Model $person): bool => $record->isOwnedBy($person))
            ->values()
            ->toBase();
    }

    /**
     * This method returns the person with the key, or null. The query()
     * method controls which people it can find.
     */
    public function find(int|string $key): ?Model
    {
        $query = $this->query();

        if ($query->getModel()->getKeyType() === 'int' && ! ctype_digit((string) $key)) {
            return null;
        }

        return $query->whereKey($key)->first();
    }

    /**
     * This method returns the person for a value from a person picker. The
     * value is the key of a person, or an email address to invite. The
     * method returns null if the person does not exist. It also returns
     * null if it cannot invite the email address.
     */
    public function resolve(int|string $value): ?Model
    {
        $value = is_string($value) ? trim($value) : $value;

        if (is_string($value) && $this->canInvite() && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return $this->invite($value);
        }

        return $this->find($value);
    }

    /**
     * This method returns true if a user can share a record with an email
     * address that has no account.
     */
    public function canInvite(): bool
    {
        return false;
    }

    /**
     * This method returns the person for an email address that the user
     * entered. If the address has an account, return that person. If not,
     * make a new person, for example with firstOrCreate(). Return null if
     * you cannot invite the address.
     */
    public function invite(string $email): ?Model
    {
        return null;
    }

    public function name(Model $person): string
    {
        return (string) $person->getAttribute('name');
    }
}
