<?php

namespace App\Notifications;

use App\Models\Intervention;
use App\Notifications\Channels\OneSignalChannel;
use App\Notifications\Messages\OneSignalMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuovoTicketAssegnatoNotification extends Notification
{
    use Queueable;

    public function __construct(public Intervention $intervention) {}

    public function via($notifiable): array
    {
        return ['database', OneSignalChannel::class];
    }

    public function toArray($notifiable): array
    {
        $priorityLabel = [
            'high' => 'Alta',
            'medium' => 'Media',
            'low' => 'Bassa',
            'fixed_date' => 'Data fissa',
        ][$this->intervention->priority] ?? $this->intervention->priority;

        return [
            'type' => 'ticket_assigned',
            'intervention_id' => $this->intervention->id,
            'intervention_title' => $this->intervention->title,
            'priority' => $this->intervention->priority,
            'priority_label' => $priorityLabel,
            'headline' => "Nuovo ticket assegnato #{$this->intervention->id}",
            'subline' => $this->intervention->title,
        ];
    }

    public function toOneSignal($notifiable): OneSignalMessage
    {
        return OneSignalMessage::create()
            ->title("Nuovo ticket #{$this->intervention->id}")
            ->body($this->intervention->title)
            ->url(route('m.tickets.index').'?open='.$this->intervention->id)
            ->data([
                'type' => 'ticket_assigned',
                'intervention_id' => $this->intervention->id,
            ]);
    }
}
