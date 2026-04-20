<?php

namespace App\Notifications;

use App\Models\InterventionCollaboration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CollaborationRespondedNotification extends Notification
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
        $responder    = $this->collaboration->user;
        $accepted     = $this->collaboration->status === InterventionCollaboration::STATUS_ACCEPTED;

        return [
            'type'              => $accepted ? 'collaboration_accepted' : 'collaboration_declined',
            'collaboration_id'  => $this->collaboration->id,
            'intervention_id'   => $intervention->id,
            'intervention_title' => $intervention->title,
            'from_user_id'      => $responder->id,
            'from_user_name'    => $responder->name,
            'headline'          => $accepted
                ? "{$responder->name} ha accettato di collaborare sul ticket #{$intervention->id}"
                : "{$responder->name} ha rifiutato di collaborare sul ticket #{$intervention->id}",
            'subline'           => $intervention->title,
        ];
    }
}
