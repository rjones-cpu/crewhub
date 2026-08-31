# CH-11 Open Decisions and Production Gates

## 1. Purpose

The following decisions must be confirmed before CH-11 becomes a production-authoritative project score. The implementation may proceed behind feature flags using clearly labeled working defaults, but Cursor must not silently treat recommendations as approved policy.

## 2. Owner decisions required

### OD-SR-01 — Evaluation window

Choose the live operational window:

- rolling 30 calendar days — recommended working default;
- rolling 14 days;
- mobilization cycle;
- calendar month;
- custom project period.

Also confirm whether monthly locked snapshots are required.

### OD-SR-02 — Workforce evaluation unit

Choose by project/use case:

- headcount by position at mobilization;
- worker-position assignment;
- worker-shift;
- worker-day;
- worker-position-day — recommended for ongoing operations.

Confirm treatment of unapproved overstaffing.

### OD-SR-03 — Arrival window and grace

Confirm:

- time-of-day arrival window;
- whether same-day late arrival remains A or becomes B;
- calendar-day calculation in project time zone;
- no-show cutoff;
- critical mobilization definition;
- authoritative source precedence.

Current baseline explicitly defines one calendar day late as B, 2–3 as C and >3/no-show as D.

### OD-SR-04 — Repeat escalation

Confirm whether:

- two B-level events in 30 days escalate to C — recommended;
- repeat classification must be same cause or same criterion;
- reset occurs when events leave the window or after a compliant period;
- overdue C corrective action can escalate to D.

### OD-SR-05 — Review deadlines

Recommended working defaults:

- B review response: 5 business days;
- C: 3 business days;
- D/critical: immediate assignment and initial response within 1 business day;
- company submission window: 5 business days.

Confirm escalation recipients and appeal level.

### OD-SR-06 — Publication model

Choose:

- automatic publication for all deterministic results;
- automatic A/B, reviewer publication for C/D;
- automatic with Under Review workflow;
- provisional D for critical events pending verification.

### OD-SR-07 — Manual evidence

Confirm:

- which sources may be manually entered;
- which require dual verification;
- expiry/supersession rules;
- acceptable attachment types;
- whether manual evidence may be used for official monthly reports.

### OD-SR-08 — Exception authority

Define project roles permitted to:

- request;
- recommend;
- approve;
- co-approve;
- renew;
- revoke;
- approve retroactive exceptions.

### OD-SR-09 — Critical override authority

Define:

- critical rule catalogue;
- HSE/project approvers;
- dual approval;
- immediate containment expectations;
- continuation/restart authority;
- resolution authority.

### OD-SR-10 — Rating recovery

Confirm whether recovery is solely calculation-window based or also requires minimum compliant periods/mobilizations after C/D. The current deterministic architecture favors evidence/window-based recovery, while corrective-action completion remains separately visible.

### OD-SR-11 — All Projects scope

Current owner-directed rule: lowest active project grade. Confirm:

- definition of active project;
- completed/archived inclusion;
- treatment of Pending Data;
- whether user filters can create a filtered overall grade, clearly labeled.

### OD-SR-12 — Formal ADR registration

CH-11 is an owner-directed working architecture decision. Add it to the canonical ADR register only after the existing ADR identifier collision is resolved.

## 3. Technical decisions required

### TD-SR-01 — Primary key convention

Cursor must use existing repository ID conventions: integer, UUID or ULID. Do not introduce a separate convention without approval.

### TD-SR-02 — Module/repository structure

Confirm whether LodgeX uses domain folders, packages/modules or conventional Laravel folders.

### TD-SR-03 — Event infrastructure

Confirm current queue, event, outbox, retry and dead-letter patterns.

### TD-SR-04 — Read model/cache

Confirm whether project/company dashboards use direct queries, cached aggregates, materialized read tables or analytics services.

### TD-SR-05 — Attachment/evidence service

Confirm existing secure file storage, scanning, signed URL, retention and classification controls.

### TD-SR-06 — Authorization framework

Confirm RBAC/ABAC implementation, project memberships, functional roles and approval authority representation.

### TD-SR-07 — Report generation

Confirm existing export/report stack and document controls.

### TD-SR-08 — AI/MCP readiness

Do not enable write-capable AI/MCP tools until tool authorization, approval, audit and evaluation architecture are approved.

## 4. Production gates

### Gate 1 — Policy approved

- criterion definitions;
- thresholds;
- evaluation window;
- source precedence;
- exception/override authority;
- review SLA;
- publication model.

### Gate 2 — Data ownership verified

- project demand source;
- company schedule source;
- arrival source;
- Journey source;
- LMS source;
- evidence versions and freshness.

### Gate 3 — Security approved

- tenant/company/project isolation;
- field-level evidence;
- segregation of duties;
- exports;
- attachment protection;
- platform support access.

### Gate 4 — Deterministic test suite passed

All threshold, N/A, exception, critical D, repeated-event, stale-data and history tests pass.

### Gate 5 — Shadow calculation accepted

Representative companies and project users validate expected results.

### Gate 6 — UI/UAT accepted

Crew Hub and Major Projects users complete end-to-end scenarios.

### Gate 7 — Operations ready

- monitoring;
- alerts;
- queue recovery;
- data-quality process;
- support runbook;
- rollback;
- incident response.

### Gate 8 — Formal release approval

Owner/business/technical/security/data/product approvals recorded.

## 5. Reference-image correction decision

The current Crew Hub visual must be corrected during implementation:

```text
Project grades shown: A, B, B, C, D
Correct All Projects Overall Grade: D
```

Do not copy the sample B as calculation logic. Preserve the visual style and B-yellow colour, but use live deterministic data.
