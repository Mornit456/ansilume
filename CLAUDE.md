# CLAUDE.md

## Purpose

This repository contains a web-based automation platform built with **PHP** and **Yii2**. The product goal is similar in spirit to tools like AWX, Tower, or Semaphore: users can manage inventories, credentials, projects, templates, and run **Ansible playbooks through a UI** with proper access control, execution history, logging, and auditability.

This file tells Claude how to work inside this repository.

---

## Product Intent

Build a production-grade, maintainable, self-hostable automation platform with these core capabilities:

- Manage users, teams, and permissions
- Store inventories, credentials, projects, playbooks, and job templates
- Launch Ansible jobs from the UI
- Track job status, logs, artifacts, and history
- Provide safe defaults, clear validations, and audit trails
- Support future API-first and worker-based scaling

The system should feel like an **operations product**, not a demo.

---

## Primary Technology Stack

- **PHP 8.2+**
- **Yii2**
- **MySQL or MariaDB**
- **Redis** for queue / cache where appropriate
- **Composer** for PHP dependencies
- **Node.js / npm** only if frontend assets require it
- **Docker / docker compose** for local development where available
- **Ansible** executed by isolated runner processes, not from the web request lifecycle

Prefer boring, stable, well-understood technologies over trendy ones.

---

## Architecture Principles

Claude must preserve these architectural rules:

1. **Thin controllers, rich services**
   - Controllers should orchestrate requests and responses only.
   - Business logic belongs in service classes, domain helpers, or dedicated action classes.

2. **No Ansible execution in the request thread**
   - Web requests create jobs.
   - Jobs are executed asynchronously by workers.
   - Job state is persisted and visible in the UI.

3. **Clear separation of concerns**
   - Models handle persistence and validation.
   - Services handle workflows.
   - Queue workers handle execution.
   - Views should stay simple.

4. **Default to secure behavior**
   - Validate all input.
   - Escape output.
   - Restrict access by role.
   - Never expose secrets in logs or HTML.

5. **Idempotent and recoverable jobs**
   - Job records must survive worker restarts.
   - State transitions must be explicit.
   - Failures must be visible and debuggable.

6. **Auditability matters**
   - Important actions should be traceable.
   - Changes to credentials, templates, launches, and permissions should be attributable to a user.

---

## What Claude Should Optimize For

When making implementation choices, optimize in this order:

1. Correctness
2. Security
3. Maintainability
4. Operational clarity
5. Simplicity
6. Performance
7. Developer convenience

Do not trade correctness or safety for speed.

---

## What We Are Building First

Prioritize the MVP in the following order:

### Phase 1: Core platform
- User authentication
- RBAC / roles / permissions
- Projects
- Inventories
- Credentials
- Job templates
- Job launch form
- Job records with status
- Worker execution pipeline
- Log output in UI

### Phase 2: Operational usability
- Job history and filtering
- Audit log
- Basic notifications
- Template variables / survey-like launch parameters
- Retry / relaunch
- Manual cancel

### Phase 3: Scale and polish
- API endpoints
- Scheduling
- Team/project scoping
- Artifact handling
- Webhooks
- Runner isolation improvements
- HA / multi-worker support

If asked to add advanced features before the basics are solid, prefer strengthening the core first.

---

## Domain Language

Use consistent naming in code and UI.

- **Project**: a source of automation content, typically a git-backed repository
- **Inventory**: hosts and groups used by Ansible
- **Credential**: SSH key, username/password, vault secret, token, etc.
- **Job Template**: reusable launch definition for a playbook execution
- **Job**: one concrete execution of a template
- **Runner**: background execution component
- **Artifact**: output produced by a job
- **Audit Log**: immutable record of meaningful user/system actions

Do not invent multiple names for the same concept.

---

## Repository Expectations

Claude should keep the repository organized roughly like this unless the existing structure already defines a good equivalent:

- `controllers/` for HTTP controllers
- `models/` for ActiveRecord models and form models
- `services/` for business logic
- `jobs/` or `queue/` for async job handlers
- `components/` for reusable framework integrations
- `views/` for Yii view files
- `migrations/` for schema changes
- `tests/` for automated tests
- `config/` for environment-aware configuration
- `runtime/` and generated files ignored in VCS

Respect the existing project structure if already present.

---

## Coding Rules

### General
- Write clear, readable, production-oriented PHP.
- Prefer small, focused classes and methods.
- Avoid clever abstractions unless they clearly reduce complexity.
- Favor explicit code over magic.
- Do not introduce a new dependency unless it clearly pays for itself.

### PHP
- Use strict typing where practical.
- Add type hints for method arguments and return values whenever possible.
- Avoid static god-classes.
- Prefer constructor injection or explicit dependency wiring over hidden globals.
- Keep methods short enough to understand without scrolling excessively.

### Yii2
- Use Yii2 conventions unless there is a strong reason not to.
- Keep controllers thin.
- Use form models for user input scenarios when ActiveRecord is not the right boundary.
- Use transactions when multiple writes must succeed together.
- Centralize repeated policy checks and workflow logic.

### Database
- Every schema change must be made through migrations.
- Never edit old migrations after they are applied; add a new migration instead.
- Add indexes deliberately.
- Model state explicitly rather than encoding meaning in nullable fields.

### Frontend / Views
- Keep UI utilitarian, clean, and operator-friendly.
- Favor clarity over flashy design.
- Job state, errors, and next actions must always be obvious.
- Never leak sensitive values to the browser.

---

## Security Requirements

These are mandatory.

1. Never commit secrets.
2. Never log raw credentials, private keys, vault secrets, tokens, or decrypted secret material.
3. Redact secrets in logs and UI.
4. Protect all state-changing actions with authorization checks.
5. Use CSRF protection for browser forms.
6. Validate and sanitize all user-controlled input.
7. Treat git repositories, playbook paths, extra vars, and shell arguments as untrusted input.
8. Avoid shell interpolation where possible.
9. When shell execution is required, escape arguments safely and keep commands auditable.
10. Runner processes must execute with the least privilege practical.

If a proposed implementation is convenient but weakens security, reject it and choose the safer path.

---

## Ansible Execution Rules

Claude must follow these rules for anything related to job execution:

- Do not execute Ansible directly from a web controller.
- Use a persisted job record before execution starts.
- Build a deterministic runner payload from project + inventory + credential + template + launch vars.
- Store stdout/stderr incrementally or in chunks suitable for UI display.
- Record start time, end time, exit code, final status, and launcher identity.
- Support at least these statuses:
  - pending
  - queued
  - running
  - succeeded
  - failed
  - canceled
- Keep runner implementation replaceable so local execution can later evolve into containerized or remote execution.

For early versions, local worker execution is acceptable if properly isolated and auditable.

---

## Data Modeling Guidance

Claude should prefer explicit tables/entities for:

- users
- roles / permissions
- teams
- projects
- inventories
- inventory_hosts / groups if needed
- credentials
- job_templates
- jobs
- job_logs
- audit_logs

Avoid storing too much opaque JSON unless it is genuinely schema-flexible data such as launch-time extra vars or artifact metadata.

---

## Testing Expectations

Claude should add or update tests for meaningful changes.

Priorities:
- Unit tests for service logic
- Integration tests for job-launch workflows
- Validation tests for form models
- Authorization tests for sensitive actions
- Migration safety where practical

At minimum, important business logic must be testable outside the UI layer.

If tests are missing, Claude should not ignore quality; instead, add targeted tests around the changed behavior.

---

## Definition of Done

A task is not done unless most of the following are true:

- The code works end-to-end for the intended use case
- Authorization is correct
- Validation is present
- Errors are handled clearly
- Migrations are included when needed
- Tests are added or updated
- No secrets are exposed
- Naming is consistent with the domain language
- The code fits the existing structure and style
- Basic operator UX is considered

---

## How Claude Should Work

When given a feature request, Claude should usually follow this sequence:

1. Understand the user goal
2. Inspect the current codebase structure
3. Identify affected models, services, controllers, views, migrations, and tests
4. Propose the simplest architecture-compatible implementation
5. Implement in small, coherent steps
6. Run relevant tests and static checks if available
7. Summarize what changed, risks, and follow-up work

If the request is underspecified, make reasonable assumptions that fit this file and document them briefly.

Do not get stuck asking unnecessary questions when a sensible default exists.

---

## Preferred Implementation Style

### Prefer
- service classes
- action classes for discrete workflows
- small helper objects for payload building and command composition
- explicit DTO-like structures when passing execution data between layers
- enums or constants for job states
- policy checks in reusable places

### Avoid
- fat controllers
- giant ActiveRecord models containing all business logic
- duplicated authorization logic everywhere
- hidden side effects in getters/setters
- mixing HTML rendering, persistence, and process execution in one class
- premature microservices

---

## Migration Strategy

When adding features:

- Create forward-only migrations
- Backfill carefully when new non-nullable columns are introduced
- Add indexes for common filters like status, template_id, project_id, created_at
- Avoid destructive changes unless clearly required
- Prefer incremental schema evolution

---

## Logging and Observability

Claude should ensure the platform is debuggable.

- Important lifecycle events should be logged
- Job execution should be inspectable from the UI
- Errors should include operator-usable context
- Sensitive values must be redacted
- Distinguish user-facing messages from internal debug details

---

## API and Extensibility

Even if the first version is UI-first, design core workflows so they can later be reused by:

- REST endpoints
- CLI commands
- schedulers
- webhooks
- external integrations

Business logic should not be trapped inside controllers.

---

## Performance Guidance

Do not optimize prematurely. However:

- Avoid N+1 queries in common list pages
- Paginate large job history views
- Stream or chunk logs sensibly
- Keep queue workers resilient for long-running jobs
- Cache only where it meaningfully reduces load and complexity remains acceptable

---

## UI Guidance

The UI should feel like an operator console.

Priorities:
- clear navigation
- predictable forms
- obvious launch flow
- visible job status and timestamps
- readable logs
- useful empty states
- strong feedback on validation and failures

Prefer operational clarity over visual experimentation.

---

## Things Claude Must Not Do

- Do not rewrite unrelated parts of the codebase just for style
- Do not add unnecessary frameworks or front-end rewrites
- Do not replace Yii2 unless explicitly asked
- Do not introduce breaking schema changes casually
- Do not bypass permissions for convenience
- Do not store secrets in plain logs, HTML, JS, or fixtures
- Do not collapse architecture into a single monolithic controller/model
- Do not leave TODO-only implementations presented as complete

---

## When Tradeoffs Are Needed

If a tradeoff is unavoidable, prefer:

- boring over clever
- explicit over magical
- secure over convenient
- maintainable over short
- incremental over sweeping rewrites

---

## Expected Local Commands

Claude should inspect the repository and use the commands that actually exist. Typical examples may include:

```bash
composer install
php yii migrate
php yii test
phpunit
vendor/bin/phpunit
php yii serve
```

If frontend assets exist:

```bash
npm install
npm run build
npm run dev
```

If Docker exists:

```bash
docker compose up -d
```

Do not assume all commands exist; verify first.

---

## Good Feature Examples

Examples of good implementation directions:

- Add a `JobLaunchService` instead of embedding launch logic in a controller
- Add a `JobRunnerPayloadBuilder` to create deterministic execution input
- Add a `CredentialRedactor` or equivalent for safe logging/display
- Add policy helpers for template launch permissions
- Add tests around job state transitions

---

## North Star

This project should become a dependable automation control plane for operators.

Every change should move the repository toward:
- clearer execution flows
- safer credential handling
- better auditability
- easier maintenance
- more predictable operations

When in doubt, build the thing an infrastructure team would trust to use.

