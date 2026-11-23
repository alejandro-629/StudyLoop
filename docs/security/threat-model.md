# Threat model: organizations, cohorts and invitations

Scope: sign-in, the organization switcher, cohort membership and invitation links (weeks 1–2). Learning content, reviews and suggested items get their own sections as they land.

## Assets

- Membership of organizations and cohorts, and the role in each (who can see whose material and, later, learners' review history).
- Learner accounts (email, name, password hash).
- Invitation links: whoever holds a valid link can join a cohort with the role it carries.

## Actors

- **Learner** trying to see other cohorts or gain staff rights.
- **Instructor or TA** trying to act outside their cohort or raise their own (or a friend's) role.
- **Member of another organization**, including its admins.
- **Outsider** with no account, possibly holding a leaked or old invitation link.

## Threats and mitigations

| # | Threat | Mitigation | Tested in |
|---|---|---|---|
| T1 | Cross-organization access by changing an id in a URL | Every route checks a policy; cohort and invitation routes use scoped bindings, so an invitation id from another cohort is a 404 | `AccessMatrixTest`, `CohortInvitationsTest` |
| T2 | Selecting a foreign organization in the switcher (tampered Livewire call or session value) | `CurrentOrganization` authorizes `view` before storing and re-checks membership on every request | `OrganizationSwitcherTest` |
| T3 | Instructor escalates someone (or a second account) to instructor | Only org admins may invite instructors; checked in the policy and in the `InviteToCohort` action | `RolePoliciesTest`, `CohortInvitationsTest` |
| T4 | Accepting an invitation to change an existing role | Acceptance never updates an existing enrollment or membership | `InvitationAcceptanceTest` |
| T5 | Guessing or editing invitation links | Links are HMAC-signed over path, id and expiry with `APP_KEY`; any change gives 403 | `InvitationAcceptanceTest` |
| T6 | Reusing a link (forwarded email, two tabs, replay) | The invitation row is locked while it is accepted and marked used; reuse gives 410 | `InvitationAcceptanceTest`, `InvitationJourneyTest` |
| T7 | Old links in mailboxes | Links expire with the invitation (default 7 days); re-inviting withdraws the previous link; staff can withdraw links | `InvitationAcceptanceTest`, `CohortInvitationsTest` |
| T8 | Someone else's account accepts a link | The signed-in account's email must match the invited address (403 otherwise) | `InvitationAcceptanceTest` |
| T9 | Taking over an existing account through a sign-up link | If the address already has an account, the link sends the person to sign in instead of creating a user | `InvitationAcceptanceTest` |
| T10 | Password guessing | Sign-in is limited to 5 attempts per email and IP; the error never says which field was wrong | `LoginTest` |
| T11 | Session fixation | The session id is regenerated on sign-in and invalidated on sign-out | not yet covered by a test |
| T12 | Hidden links mistaken for protection | Navigation is built from the same policies, and the matrix test asserts both the hidden link and the 403 | `AccessMatrixTest` |

## Accepted risks and open items

- Signatures cover the path and query, not the host (`signed:relative`). This keeps links working behind proxies; it does not weaken them, because the key is secret and the path includes the invitation id.
- Organization pages answer 403 rather than 404, so a member can learn that a slug exists. Slugs are not secret (names appear in emails).
- There is no email verification for invited addresses beyond opening the link; whoever controls the mailbox controls the account.
- No audit log of invitations and role changes yet; planned with the reporting module.
- Rate limits live in the cache store; production must use a shared store such as Redis (as the Compose stack does) so limits hold across app servers.
