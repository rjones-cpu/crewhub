# Cursor Phase Prompts — LodgeX CH-11 Service Rating

Use one phase at a time after the master repository plan is approved.

## Phase 0 — Repository intake

> Inventory the repository using `CURSOR_REPOSITORY_INTAKE_CHECKLIST.md`. Do not edit code. Return the exact existing models, tables, services, policies, routes, events, components and tests that CH-11 should reuse. Identify naming/ID conflicts and open decisions.

## Phase 1 — Domain and policy foundation

> Implement the smallest approved domain foundation: grade/criterion/status enums, policy-version validation, value objects and migrations. Follow existing repository patterns. Active policies must be immutable. Add unit and database tests. Do not implement UI or AI.

## Phase 2 — Evidence adapters and deterministic evaluators

> Implement typed evidence adapters through existing application services and the four deterministic criterion evaluators. Add all threshold boundary, N/A, stale/conflict, exception and critical-D tests from the package. Do not publish user-visible grades yet.

## Phase 3 — Snapshots, idempotency and events

> Implement immutable snapshots, criterion results, evidence references, calculation traces, idempotent recalculation jobs and after-commit events. Add concurrency, duplicate-event, retry and replay tests. Keep feature in shadow mode.

## Phase 4 — Crew Hub read experience

> Implement shared Service Rating components and Crew Hub CH-01/CH-11 read-only pages using the UI functional design. Overall Rating must be a primary top widget, B yellow, bus icon for journeys, and All Projects must use the lowest active project grade. Include loading, N/A, Data Stale, Under Review and permission states.

## Phase 5 — Major Projects performance experience

> Implement MP-08 project dashboard, company performance table and company score detail. Include A/B/C/D distribution, D priority, criterion drill-down, policy version and data freshness. Do not add grade editing.

## Phase 6 — Review and dispute workflow

> Implement Crew Hub review wizard and MP-09 review queue/workspace with validated states, reason codes, evidence references, SLAs and notifications. Decisions may confirm, accept correction, approve exception, deny or refer a defect; they must not directly set a grade. Recalculation creates a new snapshot.

## Phase 7 — Exceptions, critical overrides and corrective actions

> Implement effective-dated exceptions, restricted critical overrides that force D, and shared corrective actions. Enforce segregation of duties, non-waivable rules, audit and project verification. Add complete authorization and state-transition tests.

## Phase 8 — Reports, AI and MCP

> Implement permission-scoped reports, then AI explanation and MCP read/request capabilities only after deterministic and authorization foundations pass. AI/MCP must use application services, inherit user scope and never set or hide grades.

## Phase 9 — Production hardening

> Implement observability, performance improvements, data-quality reconciliation, shadow comparison, feature flags, rollout and rollback. Run the full acceptance plan and produce a production-readiness report identifying any unresolved gates.
