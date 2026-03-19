# READ_FIRST.md

## Purpose
This file tells the coding AI how to work inside `nino-backend` with minimal token waste and maximum execution clarity.

---

## Required File Read Order
At the start of every session, read files in this exact order:

1. `PHASES.md`
2. `PROJECT_PROGRESS.md`
3. `ARCHITECTURE.md` only if the current work touches architecture, module boundaries, security, performance, or cross-module design
4. `phases-tasks.md` only for the current phase / module / task IDs being implemented

Do NOT scan every planning file on every turn unless necessary.

---

## File Roles

### `PHASES.md`
Primary execution state file.  
Use it to know:
- current active phase
- completed task IDs
- in-progress task IDs
- next task IDs
- blocked task IDs
- module-by-module status

This is the first source of truth for what to do next.

### `PROJECT_PROGRESS.md`
Short implementation handoff log.  
Use it to know:
- what was completed in the latest batch
- what changed
- key decisions
- immediate next work
- blockers

This must remain concise.

### `ARCHITECTURE.md`
Stable technical blueprint.  
Use it only when work touches:
- module/domain boundaries
- authorization model
- event-driven flows
- payment/shipping adapters
- settings architecture
- performance-sensitive design
- shared patterns

Do not rewrite this file unless a real architectural change is made.

### `phases-tasks.md`
Full master roadmap.  
Use it only to understand the detailed tasks and acceptance criteria for the active task IDs or current phase.
This file is the long-form source of truth for project scope.
Do not repeatedly re-read the whole file if current work is already clear.

---

## Implementation Rules

1. Work in small batches.
2. Complete one module or submodule at a time.
3. Do not jump to unrelated modules unless required by dependency.
4. Do not start Community UI before Phase 1 is clean and explicitly advanced.
5. Keep controllers thin.
6. Keep business logic in services/actions/domain classes.
7. Use policies and scoped permissions for authorization.
8. Use queues for notifications and async work.
9. Avoid N+1 queries.
10. Keep admin UI professional, dense, and fast. Never playful.

---

## Status Rules for `PHASES.md`

Use only these statuses:
- `TODO`
- `IN_PROGRESS`
- `DONE`
- `BLOCKED`
- `DEFERRED`

Prefer task IDs over long status descriptions.

Example:
- `P1-IAM-01 — DONE`
- `P1-IAM-02 — IN_PROGRESS`
- `P1-ADMIN-01 — TODO`

---

## Required Updates After Each Meaningful Batch

After every meaningful implementation batch, update:

### `PHASES.md`
Update:
- completed task IDs
- in-progress task IDs
- next task IDs
- blocked task IDs if any
- module status summary if changed

### `PROJECT_PROGRESS.md`
Update:
- last completed batch summary
- changed areas
- decisions made
- immediate next tasks
- blockers if any

Keep both files concise and low-token.

---

## Decision Rules

If the current task is clear from `PHASES.md` and `PROJECT_PROGRESS.md`:
- do not re-read all planning files
- continue implementation directly

If a new module is starting:
- check `ARCHITECTURE.md`
- check only the relevant section in `phases-tasks.md`

If architecture conflicts appear:
- follow `ARCHITECTURE.md`
- record the issue briefly in `PROJECT_PROGRESS.md`
- update `PHASES.md` status if blocked

---

## Security Rules
- Never send plain passwords by email, SMS, or WhatsApp
- Use set-password link, magic link, or OTP
- Audit sensitive actions
- Validate all writes
- Use secure credential storage

---

## Output Discipline
Do not produce long explanations unless necessary.
Focus on:
- implementation
- concise progress updates
- clear next task handoff