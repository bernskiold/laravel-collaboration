<?php

use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Notifications\SharedWithYou;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->owner = user('Olivia Owner');
    $this->ada = user('Ada Lovelace');
});

it('notifies the person that gets access', function () {
    $post = post($this->owner, 'Quarterly plan');

    $post->shareWith($this->ada, SharePermission::Edit, $this->owner);

    Notification::assertSentTo($this->ada, SharedWithYou::class, function (SharedWithYou $notification) use ($post): bool {
        $mail = $notification->toMail($this->ada);

        return $notification->record->is($post)
            && $notification->permission === SharePermission::Edit
            && $mail->subject === 'Olivia Owner shared Quarterly plan with you'
            && $mail->actionUrl === 'https://example.com/posts/'.$post->id;
    });
});

it('does not notify again when the permission changes', function () {
    $post = post($this->owner);
    $post->shareWith($this->ada, SharePermission::View, $this->owner);
    $post->shareWith($this->ada, SharePermission::Edit, $this->owner);

    Notification::assertSentToTimes($this->ada, SharedWithYou::class, 1);
});

it('sends no notification when the record returns none', function () {
    note($this->owner)->shareWith($this->ada, sharedBy: $this->owner);

    Notification::assertNothingSent();
});

it('sends no notification when the notifications are off', function () {
    config()->set('collaboration.notifications.enabled', false);

    post($this->owner)->shareWith($this->ada, sharedBy: $this->owner);

    Notification::assertNothingSent();
});

it('sends no notification when there is no default', function () {
    config()->set('collaboration.notifications.default', null);

    post($this->owner)->shareWith($this->ada, sharedBy: $this->owner);

    Notification::assertNothingSent();
});

it('has no link if the URL is null, and no name if the person that shared is null', function () {
    $note = note($this->owner, 'Minutes');

    $mail = (new SharedWithYou($note, null, SharePermission::View))->toMail($this->ada);

    expect($mail->subject)->toBe('Minutes is shared with you')
        ->and($mail->actionUrl)->toBeNull();
});
