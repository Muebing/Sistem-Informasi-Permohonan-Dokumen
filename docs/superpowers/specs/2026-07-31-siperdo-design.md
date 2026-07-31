# SIPERDO — Design Document

Date: 2026-07-31
Status: Approved

## 1. Overview

**SIPERDO (Sistem Informasi Permohonan Dokumen)** is a production-ready web application for a
government agency to manage document feasibility requests ("permohonan dokumen kelayakan") from
companies/applicants. Each request flows through a multi-step approval workflow:
Draft → Upload → Submit → Review → Approved | Revision (→ resubmit → re-review) | Rejected.

Target data volume: 10,000 projects, 1,000 applicants, 1,000 reviewers. The design prioritizes
scalability, performance, maintainability, security, clean architecture, and UX.

## 2. Architecture

- **Backend**: Laravel 13 REST API (chosen over Laravel 12: already installed, newer, fully
  compatible with all required packages). PHP 8.3.
- **Frontend**: Vue 3 SPA (Composition API) served from `resources/views/app.blade.php` via the
  `{any}` catch-all route in `routes/web.php`. Vite build. Reuses the existing shadcn-vue/reka-ui
  component kit in `resources/js/components/ui/*`.
- **API prefix**: all endpoints mounted under `/api` (standard practice; the spec's endpoint paths
  apply under that prefix). Token auth via Laravel Sanctum (`personal_access_tokens`).
- **Layers** (clean architecture):
  - Controllers (thin) → Form Requests (validation) → Service classes (business logic) → Repositories (data access) → Models.
  - API Resources serialize responses.
  - Policies enforce authorization; Spatie role/permission middleware guards endpoints.
- **Cross-cutting**: Queue (document processing, notifications), Cache (dashboard stats),
  Storage (uploads), Excel + PDF export.

## 3. Roles & Permissions (Spatie)

| Role | Description |
|---|---|
| `admin` | Full access, user/role management |
| `applicant` (pemohon) | Own projects: create/edit/delete (draft), upload, submit, view own history |
| `reviewer` (penilai) | View all submissions, review (approve/reject/request revision), view all history |

Permissions: `projects.create|view|update|delete|submit`, `documents.upload`,
`reviews.create|view`, `dashboard.view`, `projects.export`.

## 4. Database (PostgreSQL)

Tables (all per spec, plus Spatie tables and supporting tables):

- `users`, `password_reset_tokens`, `sessions`, `personal_access_tokens` (Sanctum), `jobs`,
  `failed_jobs`, `cache`, `cache_locks`
- Spatie: `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`
- `projects`: id, project_code (unique, e.g. `SPRD-YYYY-XXXXX`), project_name, description,
  applicant_id (FK users), status, submitted_at (nullable), timestamps
- `project_documents`: id, project_id (FK), original_name, stored_name (unique), path, mime_type,
  size, uploaded_by, timestamps
- `reviews`: id, project_id (FK), reviewer_id (FK), status, notes, timestamps
- `approval_logs`: id, project_id (FK), user_id (FK), action, old_status, new_status, notes, timestamps
- `activity_logs`: id, user_id, project_id (nullable), activity, ip_address, user_agent, timestamps

Status stored as a PHP backed enum with a DB `check` constraint:
`draft | submitted | under_review | revision | approved | rejected`.

Indexes (per spec): `projects.status`, `projects.submitted_at`, `projects.applicant_id`,
`projects.project_code`, `projects.created_at`, `project_documents.project_id`,
`reviews.project_id`, `reviews.reviewer_id`, `reviews.status`, `approval_logs.project_id`,
`approval_logs.created_at`.

## 5. Approval Workflow

```
draft ──submit──▶ submitted ──assign/review──▶ under_review ──approve──▶ approved
                    ▲                              │
                    │ resubmit                     ├──reject──▶ rejected
                    │                              └──revision──▶ revision
                    └─────────────────────────────────────────────┘
```
- Applicant may edit/delete **only** while `draft`; during `revision` may update documents and resubmit.
- Reviewer transitions: `submitted|revision` → `under_review` → `approved|rejected|revision`.
- Every transition writes an `approval_logs` row and an `activity_logs` row.

## 6. API Surface

Auth: `POST /api/register`, `POST /api/login`, `POST /api/logout`, `GET /api/me`,
`POST /api/forgot-password`, `POST /api/reset-password`.

Projects: `GET /api/projects` (cursor pagination + search + filter + sort), `POST /api/projects`,
`GET /api/projects/{id}`, `PUT /api/projects/{id}`, `DELETE /api/projects/{id}`,
`POST /api/projects/{id}/submit`, `POST /api/projects/{id}/documents`,
`GET /api/projects/{id}/history`.

Reviews: `GET /api/reviews`, `POST /api/reviews`.

Dashboard: `GET /api/dashboard`. Export: `GET /api/projects/export/excel`,
`GET /api/projects/export/pdf`, `GET /api/projects/{id}/pdf`.

## 7. Security

- Sanctum token auth (Bearer). Form Request validation everywhere.
- Policies: applicants only manage their own projects; reviewers only review.
- Role middleware (`role:reviewer|admin`, `role:applicant`, etc.).
- Upload validation: `pdf|doc|docx`, max 5 MB, unique stored filename (UUID) in Laravel Storage,
  metadata persisted. Queue-based processing.
- Laravel defaults kept (hashed passwords, CSRF on web routes, rate limiting on auth).

## 8. Performance

- Database indexes on all hot columns; FK constraints.
- Cursor pagination on the projects list; eager loading with column selection; no N+1.
- Dashboard summaries cached (`cache`) keyed by role/user, invalidated on status change via model events.
- Queue for document processing and notifications.

## 9. Frontend

Pages: Login, Register, Forgot/Reset Password, Dashboard (applicant + reviewer, ApexCharts),
Projects list (search/filter/sort/pagination), Project create/edit/detail (documents, history,
reviews), Review, History, Profile.

Stores (Pinia): auth, project. Axios instance with auth interceptor. Reusable components:
PageHeader, StatusBadge, EmptyState, ConfirmDialog, DataTable, StatCard, charts, toast, spinner.

## 10. Testing

PHPUnit feature tests (PostgreSQL test database `siperdo_test`, `RefreshDatabase`):
Authentication, Project CRUD + workflow transitions, Approval workflow (submit/review/approve/
revision/reject), document upload validation.

Note: this machine's PHP lacks `pdo_sqlite`, so tests run against PostgreSQL.

## 11. Data Seeding

Roles/permissions → admin user → 1,000 applicants + 1,000 reviewers → 10,000 projects (chunked
factories with realistic status mix, submitted_at spread across 12 months).

## 12. Delivery

- Git commits per module (`feat: authentication`, `feat: project module`, ...).
- Full README (install, env, API docs, roles, workflow, ERD, folder structure, default accounts,
  performance notes) and CI workflow.
