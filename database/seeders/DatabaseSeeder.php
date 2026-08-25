<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@electro-pi.test',
            'password' => 'password',
        ]);

        $secondUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@electro-pi.test',
            'password' => 'password',
        ]);

        $activeProject = Project::factory()->for($demoUser)->active()->create([
            'name' => 'Website Redesign',
            'description' => 'Marketing site refresh for Electro PI.',
        ]);

        $completedProject = Project::factory()->for($demoUser)->create([
            'name' => 'API Hardening',
            'description' => 'Security and validation improvements.',
            'status' => ProjectStatus::Completed,
        ]);

        Project::factory()->for($demoUser)->create([
            'name' => 'Legacy Cleanup',
            'description' => 'Archived internal tools.',
            'status' => ProjectStatus::Archived,
        ]);

        Task::factory()->for($activeProject)->create([
            'title' => 'Draft homepage wireframes',
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        Task::factory()->for($activeProject)->create([
            'title' => 'Collect brand assets',
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Todo,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Task::factory()->for($activeProject)->overdue()->create([
            'title' => 'Finalize color palette',
            'priority' => TaskPriority::Low,
        ]);

        Task::factory()->for($completedProject)->done()->create([
            'title' => 'Add Form Request validation',
            'priority' => TaskPriority::High,
            'due_date' => now()->subDays(1)->toDateString(),
        ]);

        Project::factory()
            ->for($secondUser)
            ->active()
            ->has(Task::factory()->count(2))
            ->create([
                'name' => 'Other User Project',
            ]);
    }
}
