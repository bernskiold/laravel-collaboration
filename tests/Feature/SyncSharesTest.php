<?php

use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Events\RecordShared;
use Bernskiold\LaravelCollaboration\Events\RecordUnshared;
use Bernskiold\LaravelCollaboration\Events\SharePermissionChanged;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->owner = user('Olivia Owner');
    $this->ada = user('Ada Lovelace');
    $this->alan = user('Alan Turing');
    $this->post = post($this->owner);
});

it('adds, changes and removes shares to match the list', function () {
    $this->post->syncShares([
        $this->ada->id => SharePermission::View,
        $this->alan->id => SharePermission::Edit,
    ], $this->owner);

    $this->post->syncShares([
        $this->ada->id => SharePermission::Edit,
    ], $this->owner);

    $shares = $this->post->shares()->get();

    expect($shares)->toHaveCount(1)
        ->and($shares->first()->shared_with_id)->toBe($this->ada->id)
        ->and($shares->first()->permission)->toBe(SharePermission::Edit)
        ->and($shares->first()->shared_by_id)->toBe($this->owner->id);
});

it('can use a string as the permission', function () {
    $this->post->syncShares([$this->ada->id => 'edit'], $this->owner);

    expect($this->post->permissionFor($this->ada))->toBe(SharePermission::Edit);
});

it('does not share with the owner or with unknown people', function () {
    $this->post->syncShares([
        $this->owner->id => SharePermission::View,
        999 => SharePermission::View,
        $this->ada->id => SharePermission::View,
    ], $this->owner);

    expect($this->post->shares()->pluck('shared_with_id')->all())->toBe([$this->ada->id]);
});

it('throws an exception for a permission that the record does not offer', function () {
    note($this->owner)->syncShares([$this->ada->id => SharePermission::Edit], $this->owner);
})->throws(InvalidArgumentException::class, 'cannot share a');

it('dispatches an event for each change', function () {
    Event::fake([RecordShared::class, SharePermissionChanged::class, RecordUnshared::class]);

    $this->post->syncShares([$this->ada->id => SharePermission::View, $this->alan->id => SharePermission::View], $this->owner);
    $this->post->syncShares([$this->ada->id => SharePermission::Edit], $this->owner);

    Event::assertDispatchedTimes(RecordShared::class, 2);
    Event::assertDispatched(SharePermissionChanged::class, fn (SharePermissionChanged $event): bool => $event->sharedWith->is($this->ada)
        && $event->from === SharePermission::View
        && $event->to === SharePermission::Edit
        && $event->changedBy->is($this->owner));
    Event::assertDispatched(RecordUnshared::class, fn (RecordUnshared $event): bool => $event->sharedWith->is($this->alan)
        && $event->permission === SharePermission::View);
});

it('dispatches nothing when nothing changes', function () {
    $this->post->syncShares([$this->ada->id => SharePermission::View], $this->owner);

    Event::fake([RecordShared::class, SharePermissionChanged::class, RecordUnshared::class]);

    $this->post->syncShares([$this->ada->id => SharePermission::View], $this->owner);

    Event::assertNothingDispatched();
});

it('records one activity entry for each type of change, with the names', function () {
    $this->post->syncShares([$this->ada->id => SharePermission::View, $this->alan->id => SharePermission::View], $this->owner);
    $this->post->syncShares([$this->ada->id => SharePermission::Edit], $this->owner);

    $entries = Activity::query()->orderBy('id')->get();

    expect($entries->pluck('event')->all())->toBe(['shared', 'share-changed', 'unshared'])
        ->and($entries->first()->log_name)->toBe('post')
        ->and($entries->first()->subject->is($this->post))->toBeTrue()
        ->and($entries->first()->causer->is($this->owner))->toBeTrue()
        ->and($entries->first()->properties['people'])->toBe(['Ada Lovelace', 'Alan Turing'])
        ->and($entries->last()->properties['people'])->toBe(['Alan Turing']);
});

it('records no activity when the activity log is off', function () {
    config()->set('collaboration.activity_log.enabled', false);

    $this->post->syncShares([$this->ada->id => SharePermission::View], $this->owner);

    expect(Activity::query()->count())->toBe(0);
});

it('can remove a soft-deleted person', function () {
    $this->post->syncShares([$this->ada->id => SharePermission::View], $this->owner);
    $this->ada->delete();

    $this->post->syncShares([], $this->owner);

    expect($this->post->shares()->count())->toBe(0)
        ->and(Activity::query()->where('event', 'unshared')->first()->properties['people'])->toBe(['Ada Lovelace']);
});

it('returns the shares with the people', function () {
    $shares = $this->post->syncShares([$this->ada->id => SharePermission::View], $this->owner);

    expect($shares)->toHaveCount(1)
        ->and($shares->first()->relationLoaded('sharedWith'))->toBeTrue();
});
