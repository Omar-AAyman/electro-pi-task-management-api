<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->task->loadMissing('project');

        return (new MailMessage)
            ->subject('Task overdue: '.$this->task->title)
            ->line('The following task is overdue.')
            ->line('Project: '.$this->task->project->name)
            ->line('Task: '.$this->task->title)
            ->line('Due date: '.$this->task->due_date->toDateString());
    }
}
