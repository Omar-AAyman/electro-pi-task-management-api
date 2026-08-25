<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $project->tasks()
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']))
            ->when(
                isset($filters['search']),
                fn ($query) => $query->where('title', 'like', '%'.$filters['search'].'%')
            )
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(User $user, mixed $id): Task
    {
        return Task::query()->ownedBy($user)->whereKey($id)->firstOrFail();
    }

    public function createForProject(Project $project, array $attributes): Task
    {
        return $project->tasks()->create($attributes);
    }

    public function update(Task $task, array $attributes): Task
    {
        $task->update($attributes);

        return $task->fresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function statsForUser(User $user): object
    {
        $today = now()->toDateString();
        $pendingStatuses = [
            TaskStatus::Todo->value,
            TaskStatus::InProgress->value,
        ];

        return Task::query()
            ->ownedBy($user)
            ->selectRaw('COUNT(*) as total_tasks')
            ->selectRaw(
                'SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) as completed_tasks',
                [TaskStatus::Done->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN tasks.status IN (?, ?) THEN 1 ELSE 0 END) as pending_tasks',
                $pendingStatuses
            )
            ->selectRaw(
                'SUM(CASE WHEN tasks.due_date < ? AND tasks.status != ? THEN 1 ELSE 0 END) as overdue_tasks',
                [$today, TaskStatus::Done->value]
            )
            ->first();
    }
}
