# Personas

All personas are fictional. Roles are scoped to a cohort, except org admin, which is scoped to an organization.

## Dana — instructor

Teaches a 10-week web development bootcamp cohort of 35 adults. Has slides and Markdown notes for every module and no time to write quiz banks.

- **Needs:** turn existing notes into practice quickly; stay in control of what learners see; know which questions are confusing.
- **Does in StudyLoop:** uploads material, reviews suggested items, edits or rejects them with a reason, publishes decks, reads item analytics.
- **Frustration to avoid:** a question that tests something the course never covered.

## Ravi — teaching assistant

Supports two cohorts part-time.

- **Needs:** a clear queue of learner disputes with the item, the source passage and the learner's reason in one place.
- **Does in StudyLoop:** resolves disputes (accept or reject, with a reason), flags items for the instructor.
- **Cannot:** publish decks or change org settings.

## Mei — learner

Works full time and studies in 10-minute blocks, often late at night on a phone.

- **Needs:** short sessions that resume where she stopped, feedback that shows the passage she should reread, and a way to say "this question is wrong".
- **Does in StudyLoop:** completes due reviews, opens "Why?" explanations, opens disputes.
- **Frustration to avoid:** streak-shaming and sessions that break on a flaky connection.

## Tomás — org admin

Runs learning operations for a training company with several cohorts.

- **Needs:** create cohorts, invite staff and learners, manage seats, export data and see who published or retired what.
- **Does in StudyLoop:** manages members and invitations, reads the audit log.
- **Cannot by default:** see individual learners' answers.

## Planned permission summary

| Action | Org admin | Instructor | TA | Learner |
|---|---|---|---|---|
| Manage cohorts and invitations | ✅ | Own cohorts | — | — |
| Upload material, review suggestions | — | ✅ | — | — |
| Publish or retire decks | — | ✅ | — | — |
| Resolve disputes | — | ✅ | ✅ | — |
| Review due items, open disputes | — | — | — | ✅ |
| View cohort analytics (aggregates only) | ✅ | ✅ | ✅ | — |

Organizations, cohorts and invitations are enforced by Policies (`app/Policies`): org admins manage every cohort in their organization, instructors manage invitations for their own cohorts and may only invite TAs and learners, TAs and learners see only the cohorts they belong to, and nothing crosses organizations. The material, deck, dispute and analytics rows are still the target; they are enforced as those modules land.
