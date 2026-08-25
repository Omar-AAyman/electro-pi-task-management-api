<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Jobs\NotifyOverdueTasksJob;
use App\Jobs\NotifyUserOfOverdueTaskJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OverdueNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_dispatcher_queues_one_job_per_unnotified_task(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $overdue = Task::factory()->for($project)->overdue()->create();
        Task::factory()->for($project)->overdue()->create(['overdue_notified_at' => now()]);
        Task::factory()->for($project)->create([
            'status' => TaskStatus::Done,
            'due_date' => now()->subDay()->toDateString(),
        ]);

        (new NotifyOverdueTasksJob)->handle();

        Queue::assertPushed(NotifyUserOfOverdueTaskJob::class, 1);
        Queue::assertPushed(NotifyUserOfOverdueTaskJob::class, fn (NotifyUserOfOverdueTaskJob $job) => $job->task->is($overdue));
    }

    public function test_overdue_task_job_is_idempotent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->overdue()->create();

        (new NotifyUserOfOverdueTaskJob($task))->handle();
        (new NotifyUserOfOverdueTaskJob($task))->handle();

        Notification::assertSentToTimes($user, TaskOverdueNotification::class, 1);
        $this->assertNotNull($task->fresh()->overdue_notified_at);
    }

    public function test_changing_due_date_resets_overdue_notification_flag(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->overdue()->create([
            'overdue_notified_at' => now(),
        ]);

        $task->update(['due_date' => now()->addWeek()->toDateString()]);

        $this->assertNull($task->fresh()->overdue_notified_at);
    }
}
