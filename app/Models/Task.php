<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'priority', 'status', 'due_date'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updating(function (Task $task): void {
            if ($task->isDirty(['due_date', 'status'])) {
                $task->overdue_notified_at = null;
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('user_id', $user->id));
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('status', '!=', TaskStatus::Done);
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', TaskStatus::pending());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'overdue_notified_at' => 'datetime',
        ];
    }
}
