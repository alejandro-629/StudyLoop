# Role matrix

StudyLoop has two layers of roles:

- **Organization role** (`organization_user.role`): `admin` or `member`. Everyone who belongs to an organization is at least a member.
- **Cohort role** (`cohort_user.role`): `instructor`, `ta` (teaching assistant) or `learner`, per cohort. A person can be an instructor in one cohort and a learner in another.

Org admins do not need a cohort role to manage the organization's cohorts. Nothing is ever granted across organizations: an org admin of Harbor Tech has no rights in Northwind.

## What is enforced today

| Action | Org admin | Instructor (own cohort) | TA | Learner | Anyone in another org | Guest |
|---|---|---|---|---|---|---|
| See the organization in the switcher | ✅ | ✅ | ✅ | ✅ | — | — |
| Organization page (`/organizations/{slug}`) | ✅ | 403 | 403 | 403 | 403 | → sign in |
| "Organization" link in the navigation | ✅ | hidden | hidden | hidden | hidden | — |
| See the cohort on "My cohorts" | only if enrolled | ✅ | ✅ | ✅ | — | → sign in |
| Cohort invitations page | ✅ | ✅ | 403 | 403 | 403 | → sign in |
| Invite a learner or TA | ✅ | ✅ | 403 | 403 | 403 | → sign in |
| Invite an instructor | ✅ | 403 | 403 | 403 | 403 | → sign in |
| Withdraw an invitation | ✅ | ✅ | 403 | 403 | 403 | → sign in |
| Create or edit courses | ✅ | — | — | — | — | — |

"—" means there is no screen for it yet; the policy method already says no. Policies live in `app/Policies`; the matrix is checked over HTTP in `tests/Feature/Orgs/AccessMatrixTest.php` and at policy level in `tests/Feature/Orgs/RolePoliciesTest.php`.

## Rules behind the matrix

1. **Every page asks its policy.** Controllers call `Gate::authorize(...)` before loading anything else; Blade uses `@can` with the same ability, and the navigation (`App\Support\Navigation`) only lists links whose policy passes. Hiding a link is never the protection — the 403 is.
2. **Instructors cannot create instructors.** `CohortRole::invitableByInstructors()` is TA and learner only; a hand-crafted `role=instructor` request from an instructor is refused by `CohortPolicy::invite`.
3. **Invitations never change an existing role.** Accepting adds a membership and an enrollment only if they do not exist yet.
4. **The current organization is a preference, not a permission.** The switcher stores an id in the session, but `CurrentOrganization` only honours it while the user is still a member; any other id is ignored (dashboard) or refused with 403 (switcher).
5. **Unknown and forbidden look alike.** Switching to an organization that does not exist and to one you are not in both answer 403.

## Planned rows

These come with their modules and are listed in `docs/product/personas.md`: upload material and review suggestions (instructor), publish decks (instructor), resolve disputes (instructor, TA), review due items and open disputes (learner), cohort analytics in aggregate (admin, instructor, TA).
