<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_user_scoped_stats(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $active = Project::factory()->for($user)->active()->create();
        Project::factory()->for($user)->create(['status' => ProjectStatus::Completed]);
        Project::factory()->for($other)->active()->create();

        Task::factory()->for($active)->create([
            'status' => TaskStatus::Done,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        Task::factory()->for($active)->create([
            'status' => TaskStatus::Todo,
            'due_date' => now()->addDay()->toDateString(),
        ]);
        Task::factory()->for($active)->overdue()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_projects', 2)
            ->assertJsonPath('data.active_projects', 1)
            ->assertJsonPath('data.total_tasks', 3)
            ->assertJsonPath('data.completed_tasks', 1)
            ->assertJsonPath('data.pending_tasks', 2)
            ->assertJsonPath('data.overdue_tasks', 1);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }
}
