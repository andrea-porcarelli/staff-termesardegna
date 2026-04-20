<?php

namespace App\Notifications;

use App\Models\Intervention;
use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CollaboratorReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Intervention $intervention,
        public Report $report,
        public User $collaborator,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'collaborator_report_submitted',
            'intervention_id' => $this->intervention->id,
            'intervention_title' => $this->intervention->title,
            'report_id' => $this->report->id,
            'from_user_id' => $this->collaborator->id,
            'from_user_name' => $this->collaborator->name,
            'headline' => "{$this->collaborator->name} ha compilato il rapportino sul ticket #{$this->intervention->id}",
            'subline' => $this->intervention->title,
        ];
    }
}
