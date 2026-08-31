# Cursor Master Implementation Prompt — LodgeX CH-11 Service Rating

You are implementing the LodgeX CH-11 Service Rating in an existing Laravel, React/Inertia, Tailwind and MySQL application.

## Before making changes

1. Read all files in this Service Rating package, beginning with `README.md`.
2. Read the repository’s existing architecture instructions, Cursor rules and AGENTS files.
3. Inventory the current code for:
   - tenants, organizations, companies and projects;
   - Crew Hub Company Command;
   - Major Projects performance/governance;
   - worker assignments and company schedule;
   - workforce demand and contractor commitments;
   - Journey Management;
   - LMS/certification/readiness;
   - arrival/check-in/no-show records;
   - authorization and project memberships;
   - audit, workflows, notifications and attachments;
   - domain events, queues, outbox/retries;
   - API and Inertia conventions;
   - shared UI components and design tokens;
   - test conventions.
4. Identify conflicts between the package’s recommended structure and the actual repository.
5. Return a repository-specific implementation plan before editing files.

## Non-negotiable business rules

- Rating scope is company + Major Project + policy version + evaluation window.
- Criteria are Workforce Delivery, Scheduled Arrival, Journey Management when applicable, and LMS/Certification when applicable.
- The worst applicable criterion is the overall A/B/C/D grade; do not average.
- A requires all applicable criteria A.
- N/A is excluded.
- Approved effective-dated exceptions do not lower the score.
- Valid critical integrity/safety/compliance conditions force D.
- A is green, B yellow, C orange, D red.
- Crew Hub All Projects overall is the lowest active project grade; show grade distribution separately.
- The existing reference image’s top B is sample-data inconsistent because its project table includes D. Implement the rule, not the incorrect sample value.
- No routine user or endpoint may select a replacement grade.
- Active policies and historical snapshots are immutable.
- Corrected source data or approved governance decisions create a new snapshot and preserve history.
- Stale/missing source data must not automatically penalize the company.
- AI may explain and forecast but cannot calculate or change the official grade.

## Product boundaries

- Crew Hub owns the company schedule, assignments, Journey Management, LMS evidence, company-facing scorecard, source corrections, review submissions and company corrective actions.
- Major Projects owns workforce demand, project policy, project-authorized exceptions, review/override authority and project performance reporting.
- CH-11 deterministic services create snapshots.
- CH-01 displays the major Overall Rating widget.
- MP-08 consumes performance.
- MP-09 governs policy, reviews, exceptions and critical overrides.
- Enterprise Core supplies reusable identity, workflow, notification, audit and evidence services.

## Required plan output

Return:

1. repository findings;
2. reusable existing components/services;
3. proposed file changes grouped by phase;
4. data-model changes;
5. policy and calculation approach;
6. authorization plan;
7. events/queues plan;
8. Crew Hub UI plan;
9. Major Projects UI plan;
10. review/exception/override workflow plan;
11. test plan;
12. migration/backfill/feature-flag plan;
13. open questions and assumptions;
14. risks and rollback.

Do not edit code until the plan is reviewed.

## Implementation quality rules

- Use strict types and existing coding standards.
- Keep controllers thin.
- Use typed DTOs/value objects where consistent with the repository.
- Enforce authorization in application/domain layers and queries.
- Use transactions and idempotency.
- Publish events after commit.
- Avoid N+1 queries and unrestricted browser props.
- Add complete tests.
- Update documentation and architecture traceability.
