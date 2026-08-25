<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyUserOfOverdueTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(public Task $task) {}

    public function handle(): void
    {
        $task = $this->task->fresh(['project.user']);

        if (! $task || $task->trashed()) {
            return;
        }

        if ($task->status === TaskStatus::Done) {
            return;
        }

        if ($task->overdue_notified_at !== null) {
            return;
        }

        if ($task->due_date->toDateString() >= now()->toDateString()) {
            return;
        }

        $task->project->user->notify(new TaskOverdueNotification($task));

        $task->forceFill(['overdue_notified_at' => now()])->save();
    }
}
