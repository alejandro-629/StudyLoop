# Learning-science assumptions

StudyLoop builds on well-established ideas, but a portfolio demo cannot show that it improves learning. These assumptions explain design choices; none of them is a claim about results.

## Assumptions

1. **Retrieval practice helps retention.** Answering a question from memory is assumed to be more useful than rereading. Sessions therefore ask before they show.
2. **Spacing beats cramming.** Reviews are spread over growing intervals. SM-2 is the baseline because its rules are simple, public and testable; FSRS may follow behind the same interface.
3. **Feedback should point back to the source.** After a miss, the learner sees the exact passage the item came from, not a new explanation of unknown origin.
4. **Item quality matters more than item count.** A wrong or ambiguous item does damage, so every item is approved by an instructor and can be disputed by learners.
5. **Short, regular sessions fit adult learners.** Sessions are designed for about 10 minutes and resume where the learner stopped.

## Rules that follow from them

- The scheduler is deterministic: the same review history always produces the same due dates.
- A learner's day starts at 04:00 in their own timezone, so a session after midnight counts toward the day the learner thinks of as "today".
- Grading is deterministic. Short answers are compared against instructor-provided accepted answers after normalizing case, whitespace, punctuation and diacritics; anything else becomes a self-check.
- No streaks or loss-framed reminders; the session summary is neutral.

## What we will not claim

- Improved retention or grades, from a demo or from synthetic learners.
- That simulated retention curves describe real people. They are labelled as simulations wherever they appear.
