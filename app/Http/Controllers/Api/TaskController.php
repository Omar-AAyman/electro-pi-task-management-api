<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(private readonly TaskRepositoryInterface $tasks) {}

    public function index(IndexTaskRequest $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        return TaskResource::collection(
            $this->tasks->paginateForProject($project, $request->validated())
        );
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $task = $this->tasks->createForProject($project, $request->validated());

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        return new TaskResource(
            $this->tasks->update($task, $request->validated())
        );
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->tasks->delete($task);

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }
}
