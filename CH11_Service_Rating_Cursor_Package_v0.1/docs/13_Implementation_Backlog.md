# CH-11 Implementation Backlog

## 1. Backlog purpose

This backlog is organized for phased implementation in Cursor. Cursor must first map each item to the existing repository structure, identify reusable services/components and return a plan before editing code.

## 2. Epic SR-00 — Repository discovery and architecture alignment

### SR-001 Inventory current modules

**Outcome:** Identify current Crew Hub, Major Projects, Enterprise Core, auth, events, files, notifications, queues, UI component and testing architecture.

**Acceptance criteria:**

- repository map documented;
- existing ID conventions identified;
- existing company/project models confirmed;
- current schedule, journey, LMS and arrival sources located;
- current authorization approach documented;
- no duplicate service created.

### SR-002 Confirm canonical identifiers and naming

- map CH-01, CH-11, MP-08 and MP-09 to current code;
- identify legacy aliases;
- produce migration/crosswalk note;
- avoid using historical IDs for new routes/classes unless compatibility requires it.

### SR-003 Confirm open business decisions

Block production coding of unresolved policy details or use feature-flagged working defaults.

## 3. Epic SR-10 — Policy and domain foundation

### SR-101 Grade and criterion enums

- Grade A–D;
- criterion codes;
- N/A and Pending Data states;
- status labels and semantic colour tokens.

### SR-102 Policy schema and validation

- policy version model;
- JSON/config validation;
- effective dating;
- activation constraints;
- immutable active versions;
- policy diff and impact preview foundation.

### SR-103 Rating context and value objects

- evaluation window;
- percentages;
- lateness;
- evidence reference;
- source freshness;
- policy threshold.

### SR-104 Database migrations

Create policy, snapshot, criterion result, evidence link, review, exception, override and corrective-action records following current ID conventions.

## 4. Epic SR-20 — Evidence adapters

### SR-201 Major Projects demand adapter

- approved demand;
- critical positions;
- work package/date/shift/location;
- approved demand changes;
- source version.

### SR-202 Crew Hub schedule and assignment adapter

- authoritative company schedule;
- worker assignments;
- positions/qualifications;
- effective schedule changes;
- duplicate detection.

### SR-203 Arrival evidence adapter

- Smart Lodge check-in;
- gate/access integration;
- manifest/supervisor/manual sources;
- precedence and conflict status.

### SR-204 Journey evidence adapter

- applicability;
- required/compliant/noncompliant journeys;
- missed check-ins;
- critical conditions;
- restricted detail.

### SR-205 LMS/certification adapter

- applicable workers;
- verified requirements;
- expiry;
- critical certification;
- equivalencies/temporary authorization;
- affected-worker calculation.

### SR-206 Exception and override adapter

- effective scope;
- non-waivable validation;
- source and approval metadata.

## 5. Epic SR-30 — Deterministic calculation engine

### SR-301 Workforce Delivery evaluator

Implement thresholds, position-level rules, critical coverage, shortage duration, repeated B and trace.

### SR-302 Scheduled Arrival evaluator

Implement project-time-zone calendar days, no-show, critical mobilization, repeated B and source conflict.

### SR-303 Journey Management evaluator

Implement applicability, denominator, rate thresholds and critical D rules.

### SR-304 LMS and Certification evaluator

Implement unique affected-worker rate, threshold boundaries and critical D rules.

### SR-305 Overall grade selector

- worst applicable criterion;
- N/A excluded;
- critical override forces D;
- no average.

### SR-306 Data sufficiency guard

- current/stale/conflicting/insufficient;
- retain last published grade;
- prevent unsupported negative recalculation.

### SR-307 Calculation trace and evidence fingerprint

- reproducible JSON trace;
- stable fingerprint;
- source metadata;
- no sensitive unnecessary data.

## 6. Epic SR-40 — Snapshot lifecycle and events

### SR-401 Immutable snapshot persistence

- transaction;
- criterion rows;
- evidence links;
- prior/superseding relationships;
- update/delete guards.

### SR-402 Publication action

- policy-controlled publication;
- current pointer/read model;
- notifications;
- audit.

### SR-403 Idempotent recalculation job

- unique job/key;
- coalescing;
- retry;
- unchanged fingerprint skip.

### SR-404 Source-event listeners

Wire approved source events and daily checkpoint.

### SR-405 Outbox/event publication

Emit calculated, published, changed and data-quality events after commit.

## 7. Epic SR-50 — Crew Hub UI

### SR-501 Shared grade components

- GradeBadge;
- OverallRatingWidget;
- RatingStatusPill;
- DataFreshnessIndicator;
- PolicyVersionBadge.

### SR-502 Company Command integration

- top Overall Rating widget;
- correct widget order;
- All Projects lowest-grade rule;
- selected-project behavior;
- current reference visual styling.

### SR-503 Scorecard overview

- hero panel;
- criterion cards;
- improvement requirements;
- open reviews/actions;
- data quality.

### SR-504 Criterion detail tabs

Workforce, Arrivals, Journey, LMS.

### SR-505 Evidence drawer

- calculation line;
- source;
- threshold;
- action routing;
- permissions.

### SR-506 Rating history and compare

- timeline;
- snapshot table;
- compare;
- supersession.

## 8. Epic SR-60 — Major Projects UI

### SR-601 Project performance dashboard

- rating widgets;
- distribution;
- company table;
- filters;
- critical priority.

### SR-602 Company scorecard detail

- criterion tabs;
- calculation trace;
- review/action context;
- history.

### SR-603 Policy list and editor

- draft/active/superseded;
- validation;
- impact preview;
- approval/scheduling.

### SR-604 Manual evidence entry

- structured facts;
- verification;
- attachment;
- no grade field.

## 9. Epic SR-70 — Review and dispute

### SR-701 Crew Hub review wizard

Seven steps, reason codes, evidence selection, attestation and submission.

### SR-702 Major Projects review queue

Assignment, SLA, priority, conflict warning and filters.

### SR-703 Review workspace

Request, calculation/evidence and decision panels.

### SR-704 Review state machine

Validated transitions, audit and notifications.

### SR-705 Recalculation from decision

Correction/exception outcomes trigger new snapshot and link result.

## 10. Epic SR-80 — Exceptions, critical overrides and corrective actions

### SR-801 Exception request and approval

- scope;
- effective dates;
- evidence;
- non-waivable check;
- co-approval;
- recalculation.

### SR-802 Critical override

- restricted form;
- forces D;
- immediate alerts;
- resolution workflow;
- continuation/restart decision.

### SR-803 Corrective-action lifecycle

- action states;
- company ownership;
- project verification;
- evidence;
- overdue escalation;
- reopen.

### SR-804 Recovery forecast

Explain deterministic conditions to reach next grade.

## 11. Epic SR-90 — Reports, AI and MCP

### SR-901 Company scorecard export

Permission-scoped PDF/CSV or existing report format with policy/window/as-of metadata.

### SR-902 Project rating register and monthly package

- locked report;
- amendment linkage;
- unresolved review disclosure.

### SR-903 AI explanation context

Sanitized deterministic facts, source references and no decision authority.

### SR-904 MCP read resources

Current rating, snapshot, drivers, actions, reviews.

### SR-905 MCP governed request tools

Draft/submit review, add action update, request exception. No grade-setting tool.

## 12. Epic SR-100 — Quality, security and rollout

### SR-1001 Unit and feature test suite

All thresholds, states and policy versions.

### SR-1002 Authorization suite

Company/project/role/evidence matrix.

### SR-1003 Event/idempotency suite

Duplicate, retry, replay, out-of-order and concurrency.

### SR-1004 Accessibility and responsive tests

Crew Hub and Major Projects pages.

### SR-1005 Observability dashboards and alerts

Calculation, data freshness, queue, review SLA and errors.

### SR-1006 Shadow mode

Compare with expected manual cases.

### SR-1007 Pilot and production rollout

Feature flags, UAT, approvals, rollback and support runbook.

## 13. Recommended implementation order

```text
SR-00 Repository discovery
→ SR-10 Policy/domain foundation
→ SR-20 Evidence adapters
→ SR-30 Deterministic engine
→ SR-40 Snapshots/events
→ SR-50 Crew Hub read UI
→ SR-60 Major Projects read UI
→ SR-70 Reviews
→ SR-80 Governance/actions
→ SR-90 Reports/AI/MCP
→ SR-100 Production quality and rollout
```

## 14. Definition of done for each story

- code follows repository conventions;
- authorization enforced server-side;
- tests cover happy, boundary and denied paths;
- events are idempotent;
- audit included;
- loading/empty/error/stale UI included;
- accessibility considered;
- documentation updated;
- no uncontrolled cross-product write;
- no AI authority expansion;
- no mutable historical snapshot;
- acceptance criteria demonstrated.
