<?php

use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Events\RecordShared;
use Bernskiold\LaravelCollaboration\Models\Share;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;

it('makes a share on a record for a person', function () {
    $owner = user('Olivia Owner');
    $ada = user('Ada Lovelace');
    $post = post($owner);

    $share = Share::factory()->on($post)->sharedWith($ada)->sharedBy($owner)->edit()->create();

    expect($share->shareable->is($post))->toBeTrue()
        ->and($share->sharedWith->is($ada))->toBeTrue()
        ->and($share->sharedBy->is($owner))->toBeTrue()
        ->and($share->permission)->toBe(SharePermission::Edit)
        ->and($post->isSharedWith($ada, SharePermission::Edit))->toBeTrue();
});

it('makes a new person from the user factory', function () {
    $share = Share::factory()->on(post())->create();

    expect($share->sharedWith)->not->toBeNull()
        ->and($share->permission)->toBe(SharePermission::View)
        ->and($share->shared_by_id)->toBeNull();
});

it('does not record activity or dispatch events', function () {
    Event::fake([RecordShared::class]);

    Share::factory()->on(post())->create();

    Event::assertNotDispatched(RecordShared::class);
    expect(Activity::query()->count())->toBe(0);
});
