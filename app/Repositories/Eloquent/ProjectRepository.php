<?php

namespace App\Repositories\Eloquent;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->projects()
            ->withCount('tasks')
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(User $user, mixed $id): Project
    {
        return $user->projects()->whereKey($id)->firstOrFail();
    }

    public function createForUser(User $user, array $attributes): Project
    {
        return $user->projects()->create($attributes);
    }

    public function update(Project $project, array $attributes): Project
    {
        $project->update($attributes);

        return $project->fresh();
    }

    public function deleteWithTasks(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            $project->tasks()->delete();
            $project->delete();
        });
    }

    public function statsForUser(User $user): object
    {
        return $user->projects()
            ->selectRaw('COUNT(*) as total_projects')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_projects',
                [ProjectStatus::Active->value]
            )
            ->first();
    }
}
