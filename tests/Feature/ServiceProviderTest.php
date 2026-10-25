<?php

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Plain;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\Post;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\User;

it('merges the configuration', function () {
    expect(config('collaboration.table'))->toBe('shares')
        ->and(config('collaboration.notifications.enabled'))->toBeTrue();
});

it('resolves the user model from the auth configuration', function () {
    expect(Collaboration::userModel())->toBe(User::class)
        ->and(Collaboration::userTable())->toBe('users');

    config()->set('collaboration.user_model', 'App\\Models\\Member');

    expect(Collaboration::userModel())->toBe('App\\Models\\Member');
});

it('finds a shareable record from a morph alias or a class name', function () {
    $post = post();

    expect(Collaboration::findShareable('post', $post->id)->is($post))->toBeTrue()
        ->and(Collaboration::findShareable(Post::class, (string) $post->id)->is($post))->toBeTrue();
});

it('finds nothing for a type that is not shareable, or a missing record', function (string $type, string $key) {
    post();

    expect(Collaboration::findShareable($type, $key))->toBeNull();
})->with([
    'a model that is not shareable' => [Plain::class, '1'],
    'a class that is not a model' => [stdClass::class, '1'],
    'an unknown alias' => ['unknown', '1'],
    'a missing record' => ['post', '999'],
]);

it('gives the signed-in user', function () {
    $ada = user();

    expect(Collaboration::actingUser())->toBeNull();

    $this->actingAs($ada);

    expect(Collaboration::actingUser()->is($ada))->toBeTrue();
});
