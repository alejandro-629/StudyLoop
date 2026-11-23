<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CohortInvitation;
use App\Support\InvitationLinks;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CohortInvitationNotification extends Notification
{
    public function __construct(
        public readonly CohortInvitation $invitation,
        public readonly string $cohortName,
        public readonly string $courseTitle,
        public readonly string $inviterName,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function url(): string
    {
        return InvitationLinks::url($this->invitation);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('You are invited to :cohort', ['cohort' => $this->cohortName]))
            ->line(__(':inviter invited you to join :cohort (:course) as :role.', [
                'inviter' => $this->inviterName,
                'cohort' => $this->cohortName,
                'course' => $this->courseTitle,
                'role' => mb_strtolower($this->invitation->role->label()),
            ]))
            ->action(__('Accept invitation'), $this->url())
            ->line(__('The link works once and expires on :date (UTC).', [
                'date' => $this->invitation->expires_at->toDayDateTimeString(),
            ]))
            ->line(__('If you did not expect this, you can ignore this email.'));
    }
}
