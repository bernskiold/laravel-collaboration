<?php

namespace Bernskiold\LaravelCollaboration\Support;

use Bernskiold\LaravelCollaboration\Enums\ShareOutcome;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Models\Share;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The changes that SyncShares made to the shares of one record.
 *
 * In each collection, the key of each item is the key of a person.
 */
final readonly class ShareChanges
{
    /**
     * @param  Collection<int|string, SharePermission>  $added  The new shares, with their permissions.
     * @param  Collection<int|string, SharePermission>  $changed  The changed shares, with their new permissions.
     * @param  Collection<int|string, SharePermission>  $removed  The removed shares, with their last permissions.
     * @param  Collection<int, Share>  $shares  All the shares of the record after the change.
     */
    public function __construct(
        public Collection $added,
        public Collection $changed,
        public Collection $removed,
        public Collection $shares,
    ) {}

    /**
     * This method returns true if no share changed.
     */
    public function isEmpty(): bool
    {
        return $this->added->isEmpty() && $this->changed->isEmpty() && $this->removed->isEmpty();
    }

    /**
     * This method returns the result of the change for one person.
     */
    public function outcomeFor(Model|int|string $person): ShareOutcome
    {
        $key = $person instanceof Model ? $person->getKey() : $person;

        return match (true) {
            $this->added->has($key) => ShareOutcome::Added,
            $this->changed->has($key) => ShareOutcome::Changed,
            $this->removed->has($key) => ShareOutcome::Removed,
            default => ShareOutcome::Unchanged,
        };
    }
}
