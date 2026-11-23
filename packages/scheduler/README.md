# studyloop/scheduler

Framework-free scheduling code for StudyLoop. It has no Laravel, Carbon or database dependencies (a Pest architecture test in the app enforces this), so its rules can be tested with plain inputs and outputs.

## Contents

- `Grade`: SM-2 answer quality 0–5; 3 and above counts as correct.
- `StudyDay`: the learner's day as `Y-m-d`. A study day starts at 04:00 in the learner's timezone, so a review at 01:30 counts toward the previous calendar day. Supports adding days, day differences and ordering, independent of the server timezone.

The SM-2 algorithm itself comes next, written against golden fixtures.

## Tests

From the app root the package runs as the `Scheduler` Pest suite (`composer test`). On its own:

```bash
composer install
vendor/bin/pest
```
