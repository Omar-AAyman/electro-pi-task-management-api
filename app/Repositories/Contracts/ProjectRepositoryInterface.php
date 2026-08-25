<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function findForUser(User $user, mixed $id): Project;

    public function createForUser(User $user, array $attributes): Project;

    public function update(Project $project, array $attributes): Project;

    public function deleteWithTasks(Project $project): void;

    /**
     * @return object{total_projects: int, active_projects: int}
     */
    public function statsForUser(User $user): object;
}
