# Product charter

## Problem

Course creators and workplace trainers publish slides and readings, but learners forget most of the material within weeks, and writing good practice questions takes instructors hours. Generic quiz generators invent facts or test things the course never covered.

## Product

StudyLoop turns an instructor's own material into practice items that the instructor approves, then schedules reviews for each learner with a deterministic spaced-repetition algorithm. Suggested items and explanations are tied to exact source passages. The suggestion step never grades for credit, never schedules reviews and never publishes anything.

## Audience

Adult learners only (18+): universities, bootcamps and workplace training. All demo and test data is synthetic.

## Goals for the MVP

1. Instructors publish decks of approved items, each linked to a hash-verified source passage.
2. Learners complete short daily sessions with immediate feedback and per-item due dates.
3. Roles and cohorts are enforced by Policies on every request, not only in the UI.

## Non-goals

- Replacing an LMS, accredited assessment, proctoring or monitoring of learners
- Learners under 18
- Grading free text for marks with a model
- Payments

## Quality targets

These are test targets for the project, not claimed results.

| Target | How it is checked |
|---|---|
| No unapproved item is ever visible to a learner | Feature tests on due queues and deck visibility |
| Scheduler output matches golden fixtures exactly | Unit tests in `packages/scheduler` |
| Every published suggested item links to a valid, hash-verified span | Validation on accept plus a publish-time check |
| A learner in cohort A never sees cohort B content | Cross-cohort feature and channel-authorization tests |
| Answer submission P95 under 200 ms server time | Local seeded load test with hardware and dataset recorded |

Improved retention is never claimed from a demo; simulated curves are labelled as simulations.

## Scenario

1. An instructor uploads synthetic notes, "Intro to HTTP caching", and selects three sections.
2. StudyLoop proposes 12 items (multiple choice, cloze, short answer), each beside its highlighted source.
3. The instructor rejects 2, edits 1 and publishes 10 as a deck.
4. A learner completes a first session and misses the `no-store` question.
5. The explanation quotes the source passage; the item is due again tomorrow while correct items move out 6 days.
6. The learner disputes another item and a TA resolves it.
7. The instructor dashboard flags one item with low discrimination.

## Related documents

- [Personas](personas.md)
- [Learning-science assumptions](../learning-science/assumptions.md)
- [ADR 0001: modular Laravel monolith](../adr/0001-modular-laravel-monolith.md)
