<?php

namespace Bernskiold\LaravelCollaboration\Notifications;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * This is the default notification to a person that gets access to a
 * record. It is an email with a link to the record.
 *
 * To add channels or to change the message, extend this class. Then set
 * your class in the "notifications.default" configuration. You can also
 * return it from the shareNotification() method of a record.
 */
class SharedWithYou extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Model&Shareable $record,
        public readonly ?Model $sharedBy,
        public readonly SharePermission $permission,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->record->shareableName();
        $url = $this->record->shareableUrl();

        $message = (new MailMessage)
            ->subject($this->subject())
            ->line(__('collaboration::collaboration.notification.line', ['name' => $name]));

        if ($url !== null) {
            $message->action(__('collaboration::collaboration.notification.action', ['name' => $name]), $url);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->subject(),
            'url' => $this->record->shareableUrl(),
            'shareable_type' => $this->record->getMorphClass(),
            'shareable_id' => $this->record->getKey(),
            'permission' => $this->permission->value,
        ];
    }

    protected function subject(): string
    {
        $name = $this->record->shareableName();

        if ($this->sharedBy === null) {
            return __('collaboration::collaboration.notification.subject_anonymous', ['name' => $name]);
        }

        return __('collaboration::collaboration.notification.subject', [
            'name' => $name,
            'person' => Collaboration::people()->name($this->sharedBy),
        ]);
    }
}
