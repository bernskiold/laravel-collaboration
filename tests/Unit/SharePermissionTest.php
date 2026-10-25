<?php

use Bernskiold\LaravelCollaboration\Enums\SharePermission;

it('lets edit include view', function () {
    expect(SharePermission::Edit->allows(SharePermission::View))->toBeTrue()
        ->and(SharePermission::Edit->allows(SharePermission::Edit))->toBeTrue()
        ->and(SharePermission::View->allows(SharePermission::View))->toBeTrue()
        ->and(SharePermission::View->allows(SharePermission::Edit))->toBeFalse();
});

it('lists the permissions that allow a permission', function () {
    expect(SharePermission::atLeast(SharePermission::View))->toBe([SharePermission::View, SharePermission::Edit])
        ->and(SharePermission::atLeast(SharePermission::Edit))->toBe([SharePermission::Edit]);
});

it('has a label and a description from the translations', function () {
    expect(SharePermission::View->label())->toBe('Can view')
        ->and(SharePermission::Edit->description())->toBe('Can open it and change it.');
});
