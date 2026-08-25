<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_repository_scopes_records_to_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->for($owner)->create();

        $repository = app(ProjectRepositoryInterface::class);

        $this->assertTrue($repository->findForUser($owner, $project->id)->is($project));

        $this->expectException(ModelNotFoundException::class);
        $repository->findForUser($other, $project->id);
    }

    public function test_task_repository_returns_user_scoped_stats(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->for($project)->create([
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $stats = app(TaskRepositoryInterface::class)->statsForUser($user);

        $this->assertSame(1, (int) $stats->total_tasks);
    }
}
