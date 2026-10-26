<?php

use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Note;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Post;

beforeEach(function () {
    $this->owner = user('Olivia Owner');
    $this->ada = user('Ada Lovelace');
});

it('finds the owner from the owner column', function () {
    $post = post($this->owner);

    expect($post->shareOwnerColumn())->toBe('owner_id')
        ->and($post->isOwnedBy($this->owner))->toBeTrue()
        ->and($post->isOwnedBy($this->ada))->toBeFalse()
        ->and(post()->isOwnedBy($this->owner))->toBeFalse();
});

it('finds the records of an owner', function () {
    post($this->owner, 'Mine');
    post($this->ada, 'Theirs');
    post(name: 'Nobody');

    expect(Post::query()->ownedBy($this->owner)->pluck('name')->all())->toBe(['Mine'])
        ->and(Post::query()->ownedBy($this->owner->id)->count())->toBe(1)
        ->and(Post::query()->ownedBy(null)->count())->toBe(0);
});

it('finds the records that a person owns or can open', function () {
    post($this->ada, 'Mine');
    post($this->owner, 'Viewable')->shareWith($this->ada, SharePermission::View, $this->owner);
    post($this->owner, 'Editable')->shareWith($this->ada, SharePermission::Edit, $this->owner);
    post($this->owner, 'Private');

    expect(Post::query()->ownedByOrSharedWith($this->ada)->orderBy('id')->pluck('name')->all())->toBe(['Mine', 'Viewable', 'Editable'])
        ->and(Post::query()->ownedByOrSharedWith($this->ada, SharePermission::Edit)->orderBy('id')->pluck('name')->all())->toBe(['Mine', 'Editable'])
        ->and(Post::query()->ownedByOrSharedWith(null)->count())->toBe(0);
});

it('keeps the other conditions of the query', function () {
    post($this->ada, 'Mine');
    post($this->owner, 'Shared')->shareWith($this->ada, sharedBy: $this->owner);

    expect(Post::query()->where('name', 'Shared')->ownedByOrSharedWith($this->ada)->pluck('name')->all())->toBe(['Shared']);
});

it('finds no owned records for a model without an owner column', function () {
    $note = note($this->owner);
    $note->shareWith($this->ada, sharedBy: $this->owner);

    expect($note->shareOwnerColumn())->toBeNull()
        ->and($note->isOwnedBy($this->owner))->toBeTrue()
        ->and(Note::query()->ownedBy($this->owner)->count())->toBe(0)
        ->and(Note::query()->ownedByOrSharedWith($this->ada)->count())->toBe(1);
});

it('finds no shared records without a person', function () {
    post($this->owner)->shareWith($this->ada, sharedBy: $this->owner);

    expect(Post::query()->sharedWith(null)->count())->toBe(0);
});
