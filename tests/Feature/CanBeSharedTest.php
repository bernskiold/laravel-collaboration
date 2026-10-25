<?php

use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Models\Share;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Post;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->owner = user('Olivia Owner');
    $this->ada = user('Ada Lovelace');
    $this->alan = user('Alan Turing');
});

it('finds the records that are shared with a person', function () {
    $viewable = post($this->owner, 'Viewable');
    $editable = post($this->owner, 'Editable');
    post($this->owner, 'Private');

    $viewable->shareWith($this->ada, SharePermission::View, $this->owner);
    $editable->shareWith($this->ada, SharePermission::Edit, $this->owner);

    expect(Post::query()->sharedWith($this->ada)->pluck('name')->all())->toBe(['Viewable', 'Editable'])
        ->and(Post::query()->sharedWith($this->ada, SharePermission::Edit)->pluck('name')->all())->toBe(['Editable'])
        ->and(Post::query()->sharedWith($this->ada->id)->count())->toBe(2)
        ->and(Post::query()->sharedWith($this->alan)->count())->toBe(0);
});

it('tells if a record is shared with a person', function () {
    $post = post($this->owner);
    $post->shareWith($this->ada, SharePermission::View, $this->owner);

    expect($post->isSharedWith($this->ada))->toBeTrue()
        ->and($post->isSharedWith($this->ada, SharePermission::Edit))->toBeFalse()
        ->and($post->isSharedWith($this->alan))->toBeFalse()
        ->and($post->isSharedWith($this->owner))->toBeFalse()
        ->and($post->permissionFor($this->ada))->toBe(SharePermission::View)
        ->and($post->permissionFor($this->alan))->toBeNull();
});

it('uses the loaded shares without a query', function () {
    $post = post($this->owner);
    $post->shareWith($this->ada, SharePermission::Edit, $this->owner);
    $post->load('shares');

    DB::enableQueryLog();

    expect($post->isSharedWith($this->ada, SharePermission::Edit))->toBeTrue()
        ->and($post->isSharedWith($this->alan))->toBeFalse()
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('shares at the first offered permission by default', function () {
    $post = post($this->owner);

    $post->shareWith($this->ada, sharedBy: $this->owner);

    expect($post->permissionFor($this->ada))->toBe(SharePermission::View);
});

it('changes the permission of a person that has access', function () {
    $post = post($this->owner);
    $post->shareWith($this->ada, SharePermission::View, $this->owner);
    $post->shareWith($this->alan, SharePermission::View, $this->owner);

    $post->shareWith($this->ada, SharePermission::Edit, $this->owner);

    expect($post->shares()->count())->toBe(2)
        ->and($post->permissionFor($this->ada))->toBe(SharePermission::Edit);
});

it('removes the access of one person only', function () {
    $post = post($this->owner);
    $post->shareWith($this->ada, SharePermission::View, $this->owner);
    $post->shareWith($this->alan, SharePermission::Edit, $this->owner);

    $post->stopSharingWith($this->ada, $this->owner);

    expect($post->shares()->pluck('shared_with_id')->all())->toBe([$this->alan->id])
        ->and($post->permissionFor($this->alan))->toBe(SharePermission::Edit);
});

it('uses the signed-in user as the person that shares', function () {
    $this->actingAs($this->owner);

    $post = post($this->owner);
    $post->shareWith($this->ada);

    expect($post->shares()->first()->shared_by_id)->toBe($this->owner->id);
});

it('deletes the shares with the record', function () {
    $post = post($this->owner);
    $post->shareWith($this->ada, sharedBy: $this->owner);

    $post->delete();

    expect(Share::query()->count())->toBe(0);
});

it('keeps the shares of a soft-deleted record until it is force-deleted', function () {
    $note = note($this->owner);
    $note->shareWith($this->ada, sharedBy: $this->owner);

    $note->delete();

    expect(Share::query()->count())->toBe(1);

    $note->forceDelete();

    expect(Share::query()->count())->toBe(0);
});

it('uses the name attribute and has no URL by default', function () {
    $note = note($this->owner, 'Minutes');

    expect($note->shareableName())->toBe('Minutes')
        ->and($note->shareableUrl())->toBeNull();
});

it('uses the table from the configuration', function () {
    config()->set('collaboration.table', 'shareables');

    expect((new Share)->getTable())->toBe('shareables');
});
