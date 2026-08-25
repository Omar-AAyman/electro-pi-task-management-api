<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * @param  array{status?: string, priority?: string, search?: string}  $filters
     */
    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findForUser(User $user, mixed $id): Task;

    public function createForProject(Project $project, array $attributes): Task;

    public function update(Task $task, array $attributes): Task;

    public function delete(Task $task): void;

    /**
     * @return object{
     *     total_tasks: int,
     *     completed_tasks: int,
     *     pending_tasks: int,
     *     overdue_tasks: int
     * }
     */
    public function statsForUser(User $user): object;
}
