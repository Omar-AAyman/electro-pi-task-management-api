<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_foreign_project_returns_clean_not_found_message(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        Sanctum::actingAs($intruder);

        $this->getJson('/api/projects/'.$project->id)
            ->assertNotFound()
            ->assertJson(['message' => 'Project not found.'])
            ->assertJsonMissing(['exception', 'trace']);
    }

    public function test_missing_project_returns_clean_not_found_message(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/projects/99999')
            ->assertNotFound()
            ->assertJson(['message' => 'Project not found.'])
            ->assertJsonMissing(['exception', 'trace']);
    }

    public function test_foreign_task_returns_clean_not_found_message(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();
        $task = Task::factory()->for($project)->create([
            'due_date' => now()->addDay()->toDateString(),
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson('/api/tasks/'.$task->id)
            ->assertNotFound()
            ->assertJson(['message' => 'Task not found.'])
            ->assertJsonMissing(['exception', 'trace']);
    }

    public function test_guest_receives_clean_unauthenticated_message(): void
    {
        $this->getJson('/api/projects')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.'])
            ->assertJsonMissing(['exception', 'trace']);
    }
}
