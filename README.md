# Electro PI — Task Management API

REST API for a simple task management system. Built with Laravel 13 and Sanctum.

## Requirements

- PHP 8.3+
- Composer
- SQLite (default) or MySQL / PostgreSQL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Base URL with Herd: `http://electro-pi-task.test/api`

```bash
herd link electro-pi-task
```

## Environment

SQLite (default):

```env
DB_CONNECTION=sqlite
```

MySQL example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=electro_pi_task
DB_USERNAME=root
DB_PASSWORD=
```

For overdue notifications:

```env
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

```bash
php artisan queue:work
php artisan schedule:work
```

The overdue job runs daily and only notifies once per task (`overdue_notified_at`).

## Demo users

| Email | Password |
|-------|----------|
| `demo@electro-pi.test` | `password` |
| `other@electro-pi.test` | `password` |

## Auth

1. `POST /api/register` or `POST /api/login`
2. Use `data.token` as `Authorization: Bearer {token}`
3. Always send `Accept: application/json`

| Method | Endpoint | Auth |
|--------|----------|------|
| POST | `/api/register` | No |
| POST | `/api/login` | No |
| POST | `/api/logout` | Yes |

Register:

```json
{
  "name": "Omar",
  "email": "omar@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

## Projects

Statuses: `active`, `completed`, `archived`

| Method | Endpoint |
|--------|----------|
| GET | `/api/projects` |
| POST | `/api/projects` |
| GET | `/api/projects/{id}` |
| PUT/PATCH | `/api/projects/{id}` |
| DELETE | `/api/projects/{id}` |

```json
{
  "name": "Website Redesign",
  "description": "Optional",
  "status": "active"
}
```

## Tasks

Priorities: `low`, `medium`, `high`  
Statuses: `todo`, `in_progress`, `done`

| Method | Endpoint |
|--------|----------|
| GET | `/api/projects/{project}/tasks` |
| POST | `/api/projects/{project}/tasks` |
| GET | `/api/tasks/{task}` |
| PUT/PATCH | `/api/tasks/{task}` |
| DELETE | `/api/tasks/{task}` |

List filters:

- `status`
- `priority`
- `search` (title)

Example: `/api/projects/1/tasks?status=todo&priority=high&search=docs`

```json
{
  "title": "Draft homepage",
  "description": "Optional",
  "priority": "high",
  "status": "todo",
  "due_date": "2026-08-30"
}
```

## Dashboard

`GET /api/dashboard`

```json
{
  "data": {
    "total_projects": 3,
    "active_projects": 1,
    "total_tasks": 4,
    "completed_tasks": 1,
    "pending_tasks": 3,
    "overdue_tasks": 1
  }
}
```

- Pending = `todo` + `in_progress`
- Overdue = due date in the past and status is not `done`
- Counts are for the logged-in user only

## Postman

Import `postman/Electro_PI_Task_API.postman_collection.json`.

- `base_url` defaults to `http://electro-pi-task.test/api`
- `token` is set after Login / Register

## API docs (OpenAPI)

- UI: `http://electro-pi-task.test/docs/api`
- Spec: `http://electro-pi-task.test/docs/api.json`
- Snapshot in repo: `api.json`

## Tests

```bash
php artisan test
```

## Notes

Uses Form Requests, API Resources, Policies, Soft Deletes, pagination, repositories for projects/tasks, and a small `DashboardService`. Docker was skipped (local Herd setup).
