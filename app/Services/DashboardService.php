<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    /**
     * @return array{
     *     total_projects: int,
     *     active_projects: int,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     pending_tasks: int,
     *     overdue_tasks: int
     * }
     */
    public function forUser(User $user): array
    {
        $projectStats = $this->projects->statsForUser($user);
        $taskStats = $this->tasks->statsForUser($user);

        return [
            'total_projects' => (int) $projectStats->total_projects,
            'active_projects' => (int) $projectStats->active_projects,
            'total_tasks' => (int) $taskStats->total_tasks,
            'completed_tasks' => (int) $taskStats->completed_tasks,
            'pending_tasks' => (int) $taskStats->pending_tasks,
            'overdue_tasks' => (int) $taskStats->overdue_tasks,
        ];
    }
}
