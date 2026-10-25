<?php

use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Note;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Post;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\User;
use Bernskiold\LaravelCollaboration\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * @param  array<string, mixed>  $attributes
 */
function user(string $name = 'Ada Lovelace', array $attributes = []): User
{
    return User::query()->create([
        'name' => $name,
        'email' => str($name)->lower()->replace(' ', '.')->append('@example.com')->toString(),
        ...$attributes,
    ]);
}

function post(?User $owner = null, string $name = 'Quarterly plan'): Post
{
    return Post::query()->create(['name' => $name, 'owner_id' => $owner?->getKey()]);
}

function note(?User $owner = null, string $name = 'Meeting notes'): Note
{
    return Note::query()->create(['name' => $name, 'owner_id' => $owner?->getKey()]);
}
