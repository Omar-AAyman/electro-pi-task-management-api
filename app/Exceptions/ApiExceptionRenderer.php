<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionRenderer
{
    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return null;
        }

        if ($e instanceof ModelNotFoundException) {
            return self::notFound(self::modelMessage($e));
        }

        if ($e instanceof NotFoundHttpException) {
            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                return self::notFound(self::modelMessage($previous));
            }

            return self::notFound('Resource not found.');
        }

        if ($e instanceof AuthenticationException) {
            return self::json('Unauthenticated.', 401);
        }

        if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
            return self::json('This action is unauthorized.', 403);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
                'retry_after' => $retryAfter !== null ? (int) $retryAfter : null,
            ], 429)->withHeaders(
                array_filter(['Retry-After' => $retryAfter])
            );
        }

        if (! config('app.debug')) {
            return self::json('Server error.', 500);
        }

        return null;
    }

    private static function modelMessage(ModelNotFoundException $exception): string
    {
        return match (class_basename($exception->getModel())) {
            'Project' => 'Project not found.',
            'Task' => 'Task not found.',
            default => 'Resource not found.',
        };
    }

    private static function notFound(string $message): JsonResponse
    {
        return self::json($message, 404);
    }

    private static function json(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
