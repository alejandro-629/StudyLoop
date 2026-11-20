# ADR 0001: Modular Laravel monolith with a framework-free scheduler

- **Status:** accepted
- **Date:** 2026-11-20

## Context

StudyLoop has instructor tooling (uploads, review workbench, publishing), learner review sessions, queued background work (parsing, item suggestions, analytics) and a small realtime surface for instructors. One developer builds it over a few weeks, and it must run locally with one command.

## Decision

1. **One Laravel application** organized as a modular monolith by domain under `app/Domain/{Orgs,Materials,Items,Decks,Reviews,Disputes,Analytics}`. Modules talk through Action classes and events, not through each other's models.
2. **Livewire + Alpine + Tailwind** for the UI. Livewire components call Actions; they never query or authorize inline in Blade. Every model has a Policy. IDs that clients must not change are `#[Locked]`.
3. **MySQL 8 (InnoDB, `utf8mb4`)** as the system of record, with timestamps stored in UTC. **Redis** for cache, sessions and queues, supervised by **Horizon**. Failed jobs are kept in MySQL.
4. **Scheduler math in `packages/scheduler`**, a Composer path package with no Laravel imports, tested with golden fixtures.
5. **Background work only in queued jobs.** Nothing slow or external runs during a learner's review request; learner sessions work without WebSockets.
6. **Quality gates from day one:** `declare(strict_types=1)` everywhere (enforced by a Pest architecture test and Pint), Larastan at level 6 with an empty baseline, Pest for tests, Pint for formatting.
7. **Local environment:** Sail-compatible `compose.yaml` with the app, a Horizon worker, MySQL, Redis and Mailpit.

## Consequences

- One deployable, one test suite and one migration history; web, Horizon workers and Reverb can still run as separate processes from the same code.
- Module boundaries are a convention, so they need review discipline; architecture tests will guard them as modules appear.
- The scheduler can be tested and reused without booting Laravel.
- Authentication screens come from Laravel's Livewire starter kit, added together with organizations and cohorts so that sign-up, invitations and membership are designed as one flow.

## Alternatives considered

- **Separate API and SPA:** more moving parts and duplicated validation for a team of one; a small `/api/v1` is enough for the learner session.
- **Microservices per domain:** no scaling need justifies the operational cost.
- **Vector database for grounding:** each item stores its exact source span, so explanations look the span up by ID instead of by similarity search.
