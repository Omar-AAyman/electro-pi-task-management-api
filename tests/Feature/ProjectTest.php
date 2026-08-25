<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_own_projects(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/projects', [
            'name' => 'My Project',
            'description' => 'Demo project',
            'status' => ProjectStatus::Active->value,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'My Project');

        Project::factory()->for($user)->count(2)->create();

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        Sanctum::actingAs($intruder);

        $this->getJson('/api/projects/'.$project->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Project not found.');
    }

    public function test_deleting_project_soft_deletes_its_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create([
            'due_date' => now()->addDay()->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/projects/'.$project->id)->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_user_can_update_and_soft_delete_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/projects/'.$project->id, [
            'name' => 'Updated name',
            'status' => ProjectStatus::Completed->value,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated name')
            ->assertJsonPath('data.status', ProjectStatus::Completed->value);

        $this->deleteJson('/api/projects/'.$project->id)
            ->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_guest_cannot_access_projects(): void
    {
        $this->getJson('/api/projects')->assertUnauthorized();
    }
}
