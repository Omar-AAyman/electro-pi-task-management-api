<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ValidationUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_rejects_invalid_name_and_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Omar123',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_register_normalizes_email_case(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Omar Ayman',
            'email' => 'Omar.Ayman@Example.COM',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'omar.ayman@example.com',
        ]);
    }

    public function test_email_can_be_reused_after_user_is_soft_deleted(): void
    {
        $user = User::factory()->create([
            'email' => 'reuse@example.com',
        ]);

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->postJson('/api/register', [
            'name' => 'New Owner',
            'email' => 'reuse@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'reuse@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'reuse@example.com',
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'gone@example.com',
            'password' => 'password',
        ]);

        $user->delete();

        $this->postJson('/api/login', [
            'email' => 'gone@example.com',
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_project_name_must_be_unique_per_user_but_reusable_after_soft_delete(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $project = Project::factory()->for($user)->create([
            'name' => 'Website Redesign',
        ]);

        $this->postJson('/api/projects', [
            'name' => 'Website Redesign',
            'status' => ProjectStatus::Active->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->deleteJson('/api/projects/'.$project->id)->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);

        $this->postJson('/api/projects', [
            'name' => 'Website Redesign',
            'status' => ProjectStatus::Active->value,
        ])->assertCreated();
    }

    public function test_different_users_can_reuse_the_same_project_name(): void
    {
        $owner = User::factory()->create();
        Project::factory()->for($owner)->create(['name' => 'Shared Name']);

        $other = User::factory()->create();
        Sanctum::actingAs($other);

        $this->postJson('/api/projects', [
            'name' => 'Shared Name',
            'status' => ProjectStatus::Active->value,
        ])->assertCreated();
    }

    public function test_task_title_must_be_unique_within_project_and_reusable_after_soft_delete(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create([
            'title' => 'Ship docs',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/projects/'.$project->id.'/tasks', [
            'title' => 'Ship docs',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Todo->value,
            'due_date' => now()->addDays(2)->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->deleteJson('/api/tasks/'.$task->id)->assertOk();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);

        $this->postJson('/api/projects/'.$project->id.'/tasks', [
            'title' => 'Ship docs',
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Todo->value,
            'due_date' => now()->addDays(2)->toDateString(),
        ])->assertCreated();
    }
}
