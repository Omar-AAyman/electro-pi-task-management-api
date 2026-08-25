<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_filter_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/projects/'.$project->id.'/tasks', [
            'title' => 'Ship API docs',
            'description' => 'Write endpoint docs',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Todo->value,
            'due_date' => now()->addDay()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Ship API docs');

        Task::factory()->for($project)->create([
            'title' => 'Low priority chore',
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::InProgress,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->getJson('/api/projects/'.$project->id.'/tasks?status=todo&priority=high&search=Ship')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Ship API docs');
    }

    public function test_user_cannot_manage_tasks_on_foreign_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();
        $task = Task::factory()->for($project)->create([
            'due_date' => now()->addDay()->toDateString(),
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson('/api/projects/'.$project->id.'/tasks')
            ->assertNotFound()
            ->assertJsonPath('message', 'Project not found.');

        $this->putJson('/api/tasks/'.$task->id, [
            'title' => 'Hacked',
        ])->assertNotFound()
            ->assertJsonPath('message', 'Task not found.');
    }

    public function test_user_can_update_and_soft_delete_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create([
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/tasks/'.$task->id, [
            'status' => TaskStatus::Done->value,
            'priority' => TaskPriority::Medium->value,
        ])->assertOk()
            ->assertJsonPath('data.status', TaskStatus::Done->value);

        $this->deleteJson('/api/tasks/'.$task->id)
            ->assertOk();

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }
}
