<?php

use Bernskiold\LaravelCollaboration\Actions\HandOverShares;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;

beforeEach(function () {
    $this->owner = user('Olivia Owner');
    $this->duplicate = user('Ada Duplicate');
    $this->survivor = user('Ada Lovelace');
});

it('moves the shares of one person to another person', function () {
    $post = post($this->owner);
    $post->shareWith($this->duplicate, SharePermission::Edit, $this->owner);

    app(HandOverShares::class)->execute($this->duplicate, $this->survivor);

    $share = $post->shares()->sole();

    expect($share->shared_with_id)->toBe($this->survivor->id)
        ->and($share->permission)->toBe(SharePermission::Edit)
        ->and($share->shared_by_id)->toBe($this->owner->id);
});

it('keeps the larger permission when both people had access', function () {
    $larger = post($this->owner, 'Larger');
    $larger->shareWith($this->duplicate, SharePermission::Edit, $this->owner);
    $larger->shareWith($this->survivor, SharePermission::View, $this->owner);

    $smaller = post($this->owner, 'Smaller');
    $smaller->shareWith($this->duplicate, SharePermission::View, $this->owner);
    $smaller->shareWith($this->survivor, SharePermission::Edit, $this->owner);

    app(HandOverShares::class)->execute($this->duplicate, $this->survivor);

    expect($larger->shares()->sole()->permission)->toBe(SharePermission::Edit)
        ->and($smaller->shares()->sole()->permission)->toBe(SharePermission::Edit)
        ->and($larger->shares()->sole()->shared_with_id)->toBe($this->survivor->id);
});

it('deletes the shares on records of which the second person is the owner', function () {
    $theirs = post($this->survivor);
    $theirs->shareWith($this->duplicate, sharedBy: $this->survivor);

    app(HandOverShares::class)->execute($this->duplicate, $this->survivor);

    expect($theirs->shares()->count())->toBe(0);
});
