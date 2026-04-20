<?php

namespace App\Notifications;

use App\Models\InterventionCollaboration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CollaborationRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public InterventionCollaboration $collaboration) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $intervention = $this->collaboration->intervention;
        $requester = $this->collaboration->requestedBy;

        return [
            'type' => 'collaboration_requested',
            'collaboration_id' => $this->collaboration->id,
            'intervention_id' => $intervention->id,
            'intervention_title' => $intervention->title,
            'from_user_id' => $requester->id,
            'from_user_name' => $requester->name,
            'message' => $this->collaboration->message,
            'headline' => "Richiesta di collaborazione sul ticket #{$intervention->id}",
            'subline' => "Da {$requester->name}",
        ];
    }
}
