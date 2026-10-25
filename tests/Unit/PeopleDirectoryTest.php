<?php

use Bernskiold\LaravelCollaboration\Support\PeopleDirectory;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\InvitingPeopleDirectory;

it('finds people by name or email address, without the owner', function () {
    $owner = user('Ada Lovelace');
    user('Alan Turing');
    user('Grace Hopper', ['email' => 'amazing.grace@example.com']);
    user('Linus Torvalds');

    $results = (new PeopleDirectory)->search('a', post($owner), 10)->pluck('name');

    expect($results->all())->toBe(['Alan Turing', 'Grace Hopper', 'Linus Torvalds']);
    expect((new PeopleDirectory)->search('AMAZING', post($owner), 10)->pluck('name')->all())->toBe(['Grace Hopper']);
});

it('finds nothing for an empty search', function () {
    user('Alan Turing');

    expect((new PeopleDirectory)->search('  ', post(), 10))->toBeEmpty();
});

it('limits the results', function () {
    user('Alan Turing');
    user('Alan Kay');

    expect((new PeopleDirectory)->search('Alan', post(), 1))->toHaveCount(1);
});

it('does not invite by default', function () {
    expect((new PeopleDirectory)->canInvite())->toBeFalse()
        ->and((new PeopleDirectory)->invite('new@example.com'))->toBeNull();
});

it('resolves a key to a person', function () {
    $ada = user('Ada Lovelace');

    expect((new PeopleDirectory)->resolve((string) $ada->id)->is($ada))->toBeTrue()
        ->and((new PeopleDirectory)->resolve($ada->id)->is($ada))->toBeTrue()
        ->and((new PeopleDirectory)->resolve('999'))->toBeNull();
});

it('does not resolve an email address when it cannot invite', function () {
    user('Ada Lovelace');

    expect((new PeopleDirectory)->resolve('ada.lovelace@example.com'))->toBeNull()
        ->and((new PeopleDirectory)->find('ada.lovelace@example.com'))->toBeNull();
});

it('resolves an email address through an invitation', function () {
    $ada = user('Ada Lovelace');
    $directory = new InvitingPeopleDirectory;

    $grace = $directory->resolve(' grace.hopper@example.com ');

    expect($grace->name)->toBe('Grace Hopper')
        ->and($grace->exists)->toBeTrue()
        ->and($directory->resolve('ada.lovelace@example.com')->is($ada))->toBeTrue()
        ->and($directory->resolve('someone@elsewhere.test'))->toBeNull();
});
