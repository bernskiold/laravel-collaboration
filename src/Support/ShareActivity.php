<?php

namespace Bernskiold\LaravelCollaboration\Support;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * This class records the changes to the shares of a record in the activity
 * log of the record. It uses spatie/laravel-activitylog.
 *
 * Each call to SyncShares makes one entry for each type of change. The
 * "people" property of the entry has the names of the people. You can
 * read the entry after the package removes a person.
 */
class ShareActivity
{
    public const SHARED = 'shared';

    public const CHANGED = 'share-changed';

    public const UNSHARED = 'unshared';

    public function enabled(): bool
    {
        return config('collaboration.activity_log.enabled', true) && function_exists('activity');
    }

    /**
     * @param  Collection<int, Model>  $people
     */
    public function record(Model&Shareable $record, string $event, Collection $people, ?Model $performedBy): void
    {
        if ($people->isEmpty() || ! $this->enabled()) {
            return;
        }

        $directory = Collaboration::people();

        $logger = activity(config('collaboration.activity_log.log_name') ?? $record->getMorphClass())
            ->performedOn($record)
            ->event($event)
            ->withProperties([
                'people' => $people
                    ->map(fn (Model $person): string => $directory->name($person))
                    ->sort()
                    ->values()
                    ->all(),
                'people_ids' => $people->map(fn (Model $person): int|string => $person->getKey())->values()->all(),
            ]);

        if ($performedBy !== null) {
            $logger->causedBy($performedBy);
        }

        $logger->log(match ($event) {
            self::SHARED => 'shared',
            self::UNSHARED => 'removed access',
            default => 'changed access',
        });
    }

    /**
     * This method returns a description of a share entry in the activity
     * log, as a verb and an object. For example: "shared it with" and "Ada
     * Lovelace, Alan Turing". For other entries, the method returns null.
     *
     * The entry is the Activity model of spatie/laravel-activitylog.
     *
     * @return array{verb: string, object: string}|null
     */
    public static function describe(Model $entry): ?array
    {
        $names = collect(data_get($entry->getAttribute('properties'), 'people', []))->filter()->values();

        if ($names->isEmpty()) {
            return null;
        }

        $object = $names->count() > 3
            ? __('collaboration::collaboration.activity.and_more', [
                'names' => $names->take(2)->implode(', '),
                'count' => $names->count() - 2,
            ])
            : $names->implode(', ');

        $verb = match ($entry->getAttribute('event')) {
            self::SHARED => __('collaboration::collaboration.activity.shared'),
            self::UNSHARED => __('collaboration::collaboration.activity.unshared'),
            self::CHANGED => __('collaboration::collaboration.activity.changed'),
            default => null,
        };

        return $verb === null ? null : ['verb' => $verb, 'object' => $object];
    }
}
