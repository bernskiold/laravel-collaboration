<?php

namespace Bernskiold\LaravelCollaboration\Listeners;

use Bernskiold\LaravelCollaboration\Events\RecordShared;

/**
 * This listener sends the notification of the record to the person that
 * got access. The shareNotification() method of the record selects the
 * notification.
 */
class SendShareNotification
{
    public function handle(RecordShared $event): void
    {
        if (! config('collaboration.notifications.enabled', true)) {
            return;
        }

        if (! method_exists($event->sharedWith, 'notify')) {
            return;
        }

        $notification = $event->record->shareNotification($event->sharedWith, $event->sharedBy, $event->permission);

        if ($notification === null) {
            return;
        }

        $event->sharedWith->notify($notification);
    }
}
