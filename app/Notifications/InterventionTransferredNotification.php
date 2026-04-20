<?php

namespace App\Notifications;

use App\Models\Intervention;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InterventionTransferredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Intervention $intervention,
        public User $fromUser,
        public ?string $reason = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'transfer_received',
            'intervention_id' => $this->intervention->id,
            'intervention_title' => $this->intervention->title,
            'from_user_id' => $this->fromUser->id,
            'from_user_name' => $this->fromUser->name,
            'reason' => $this->reason,
            'headline' => "Ti è stato trasferito il ticket #{$this->intervention->id}",
            'subline' => "Da {$this->fromUser->name}",
        ];
    }
}
