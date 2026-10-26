<?php

use Bernskiold\LaravelCollaboration\Enums\ShareOutcome;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;

beforeEach(function () {
    $this->owner = user('Olivia Owner');
    $this->ada = user('Ada Lovelace');
    $this->post = post($this->owner);
});

it('tells what shareWith() did', function () {
    expect($this->post->shareWith($this->ada, SharePermission::View, $this->owner))->toBe(ShareOutcome::Added)
        ->and($this->post->shareWith($this->ada, SharePermission::View, $this->owner))->toBe(ShareOutcome::Unchanged)
        ->and($this->post->shareWith($this->ada, SharePermission::Edit, $this->owner))->toBe(ShareOutcome::Changed);
});

it('tells what stopSharingWith() did', function () {
    $this->post->shareWith($this->ada, sharedBy: $this->owner);

    expect($this->post->stopSharingWith($this->ada, $this->owner))->toBe(ShareOutcome::Removed)
        ->and($this->post->stopSharingWith($this->ada, $this->owner))->toBe(ShareOutcome::Unchanged);
});

it('tells that a share with the owner did nothing', function () {
    expect($this->post->shareWith($this->owner, sharedBy: $this->owner))->toBe(ShareOutcome::Unchanged)
        ->and($this->post->shares()->count())->toBe(0);
});
