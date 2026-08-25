<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyOverdueTasksJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Task::query()
            ->overdue()
            ->whereNull('overdue_notified_at')
            ->select('id')
            ->chunkById(100, function ($tasks): void {
                foreach ($tasks as $task) {
                    NotifyUserOfOverdueTaskJob::dispatch($task);
                }
            });
    }
}
