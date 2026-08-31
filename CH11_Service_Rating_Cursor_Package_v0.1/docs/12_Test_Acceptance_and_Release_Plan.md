# CH-11 Test, Acceptance and Release Plan

## 1. Objective

Prove that the Service Rating is deterministic, explainable, secure, permission-aware, historically reproducible and operationally reliable before it becomes an official project measure.

## 2. Test layers

- unit tests for value objects, enums and thresholds;
- evaluator tests for each criterion;
- policy-version tests;
- application action tests;
- database constraint tests;
- API feature tests;
- authorization and isolation tests;
- event/idempotency tests;
- queue/replay tests;
- React component tests;
- Inertia page tests;
- end-to-end user workflow tests;
- accessibility tests;
- performance/load tests;
- security tests;
- user acceptance testing;
- shadow-calculation comparison.

## 3. Deterministic calculation tests

### 3.1 Overall rule

- A/A/A/A → A;
- A/B/A/A → B;
- A/A/C/B → C;
- A/A/A/D → D;
- A/N/A/A → A;
- B/N/A/A → B;
- identical evidence and policy produce identical fingerprint/result;
- criterion order does not affect result;
- AI availability does not affect result.

### 3.2 All Projects rule

- active project grades A, B, B, C, D → Overall D;
- filtered projects A, B → Overall B;
- archived D excluded from live view by default;
- Pending Data project is counted separately and does not silently replace published grade;
- no active project shows No Active Rating, not A.

## 4. Workforce Delivery tests

Boundary values:

- 0%, 5%, 5.0001%, 10%, 10.0001%, 25%, 25.0001%;
- zero required units;
- approved excess;
- unapproved excess;
- wrong-position worker;
- critical position missing;
- critical position unavailable exactly 3 days and >3 days;
- shortage 1 day, 2 days, 3 days, 4 days;
- duplicate assignment;
- approved substitution;
- approved demand reduction;
- repeated B event escalation;
- event leaves rolling window and recovery occurs.

Assertions:

- extra non-critical workers do not offset critical shortage;
- position-level worst result controls criterion;
- exception excludes only intended scope;
- calculation trace includes numerator/denominator and threshold.

## 5. Scheduled Arrival tests

- arrival in window;
- arrival same day outside configured window;
- one calendar day late;
- two days late;
- three days late;
- four days late;
- no-show;
- critical mobilization missed;
- approved schedule change before arrival;
- retroactive unapproved change;
- project time-zone boundary;
- daylight-saving transition;
- conflicting gate and Smart Lodge records;
- manual evidence verified/unverified;
- repeated B escalation.

## 6. Journey Management tests

- criterion disabled → N/A;
- no applicable journeys → N/A;
- missing required journey record → noncompliance, not N/A;
- 0%, 20%, 20.0001%, 40%, 40.0001%;
- one journey with multiple deficiencies counted once;
- isolated non-critical deficiency;
- unauthorized high-risk journey → D;
- falsified record → D;
- incident linked to noncompliance → D;
- approved scoped exception;
- exception expired;
- restricted evidence visibility.

## 7. LMS and Certification tests

- 100% compliant → A;
- one affected worker among 100 → B;
- exactly 20% → B;
- 20.0001% → C;
- exactly 40% → C;
- 40.0001% → D;
- one worker with multiple deficiencies counted once;
- critical certification missing → D;
- falsified evidence → D;
- knowingly mobilized noncompliant worker → D;
- evidence pending verification does not count as compliant;
- course booked but not completed;
- certificate expires during assignment;
- approved equivalency;
- temporary authorization expiry;
- criterion disabled → N/A.

## 8. Exception tests

- valid effective-dated exception;
- exception before start/after end;
- wrong company/project/criterion scope;
- partial population scope;
- retroactive exception with approval;
- retroactive exception without authority;
- non-waivable critical rule rejection;
- requester cannot self-approve where segregation applies;
- exception expiry triggers recalculation;
- prior snapshots retain original decision context.

## 9. Critical override tests

- authorized override forces D;
- unauthorized user denied;
- missing evidence rejected;
- dual approval required/satisfied;
- valid D not averaged upward;
- resolution requires authority;
- resolution triggers recalculation;
- historical D retained;
- user cannot force A/B/C;
- API/MCP has no arbitrary grade tool.

## 10. Data-quality tests

- source current;
- stale source;
- integration failed;
- evidence conflict;
- insufficient denominator/source;
- last published grade retained;
- negative recalculation paused when required data missing;
- manual evidence permitted/denied;
- manual evidence later conflicts with restored source;
- reconciliation creates review item;
- Data Stale visually distinct from B/C/D.

## 11. Review/dispute workflow tests

- authorized company user submits review;
- unauthorized worker denied;
- review deadline open/closed;
- duplicate review detected;
- review assigned;
- information requested and answered;
- reviewer confirms;
- reviewer accepts correction;
- reviewer approves exception;
- reviewer denies;
- co-approval required;
- reviewer cannot enter arbitrary grade;
- recalculation creates new snapshot;
- old snapshot becomes Superseded;
- current grade remains visible Under Review;
- notifications and SLA escalation fire;
- appeal assigned to different reviewer where configured.

## 12. Corrective-action tests

- B optional action according to policy;
- C mandatory action;
- D critical mandatory action;
- state transition validation;
- company owner assignment;
- project verification;
- overdue escalation;
- evidence submission;
- close and reopen;
- closing action does not directly edit grade;
- forecast recovery updates when conditions change;
- cross-company access denied.

## 13. Policy-version tests

- draft editable;
- active immutable;
- scheduled version activates at correct project time;
- old snapshots retain old policy;
- impact preview accurate;
- retroactive activation blocked without exceptional approval;
- criterion activation effective-dated;
- source precedence versioned;
- grade colours controlled;
- invalid overlapping active versions rejected.

## 14. Authorization tests

Use a matrix of tenant, company, project, role and evidence type.

Required cases:

- Company A cannot access Company B by guessed ID;
- project reviewer sees assigned project only;
- executive sees aggregate but not protected evidence;
- HSE role sees Journey/LMS scope but not payroll;
- scheduler can correct schedule but not approve exception;
- LodgeX technical operator can rerun but not decide business exception;
- auditor read only;
- expired role/relationship denied;
- export honors same query and field scope;
- AI/MCP does not broaden access;
- attachment links are signed/expiring and authorized.

## 15. Event and idempotency tests

- duplicate event processed once;
- out-of-order event uses source version/effective time;
- queue retry does not duplicate snapshot;
- dead-letter and replay;
- transaction rollback emits no event;
- outbox publishes after commit;
- concurrent calculations resolve safely;
- unchanged evidence fingerprint skips duplicate snapshot;
- exception expiry schedules recalculation;
- event correlation chain preserved.

## 16. UI tests

### Crew Hub

- Overall Rating first major widget;
- B yellow;
- bus icon for Journeys Due;
- Accommodation Status displays reservations confirmed;
- project selector changes rating scope;
- All Projects sample with D displays D;
- criterion drill-down;
- evidence drawer permissions;
- review wizard validation;
- current grade Under Review;
- history compare;
- loading/empty/stale/error states;
- responsive layouts.

### Major Projects

- rating distribution and D count;
- company table filtering/sorting;
- policy active version read only;
- impact preview;
- review workspace decisions;
- no arbitrary grade edit;
- exception and critical override safeguards;
- corrective-action dashboard;
- aggregate executive view hides protected evidence.

## 17. Accessibility tests

- keyboard-only navigation;
- focus order and visible focus;
- screen-reader grade label;
- colour-independent status;
- table semantics;
- chart data alternatives;
- error summary and field association;
- dialog focus trapping and return;
- contrast;
- zoom/reflow;
- reduced-motion behavior where relevant.

## 18. Performance tests

Proposed test goals:

- typical single company/project calculation under 5 seconds;
- 1,000 active company-project calculations complete within scheduled window;
- project dashboard p95 under 2 seconds using read model/cache;
- review queue p95 under 2 seconds;
- event-to-published update within configured service level;
- attachment upload does not block review submission;
- no N+1 queries on company table or criterion drill-down.

## 19. Security tests

- broken object-level authorization;
- mass assignment;
- CSRF/session controls;
- injection and unsafe filters;
- malicious attachment;
- stored XSS in review comments;
- export authorization;
- audit tampering attempt;
- replay/idempotency abuse;
- privilege escalation;
- prompt injection through evidence displayed to AI;
- MCP tool authorization;
- sensitive data leakage in logs/errors.

## 20. Shadow-calculation pilot

Before official use:

1. enable feature in shadow mode;
2. calculate ratings without displaying them as official;
3. compare against manually prepared test cases;
4. investigate every difference;
5. verify source freshness and evidence mapping;
6. confirm project policy;
7. run company and project UAT;
8. sign off before publication.

## 21. User acceptance scenarios

### Company users

- understand top grade and driver;
- correct company-owned source;
- submit review;
- respond to information request;
- manage corrective action;
- view history and recovery conditions.

### Project users

- activate policy;
- review company register;
- inspect evidence;
- decide review without editing grade;
- approve exception;
- apply critical override;
- verify corrective action;
- generate report.

## 22. Release phases

### Phase 0 — Architecture and fixtures

- policy schema;
- sample calculations;
- UI prototypes;
- permissions;
- test fixtures.

### Phase 1 — Read-only shadow engine

- evaluators;
- snapshots;
- evidence adapters;
- internal dashboard;
- no official publication.

### Phase 2 — Crew Hub display

- Company Command widget;
- criterion detail;
- history;
- data quality.

### Phase 3 — Major Projects performance

- project dashboard;
- company detail;
- reporting.

### Phase 4 — Reviews and corrective actions

- dispute wizard;
- review queue;
- exceptions;
- corrective actions.

### Phase 5 — Critical governance

- critical override;
- escalations;
- locked reporting.

### Phase 6 — AI/MCP

- explanations;
- read resources;
- draft/submit governed requests;
- evaluation and security tests.

## 23. Rollback plan

- feature flags disable publication/UI separately;
- retain snapshots and audit;
- stop event consumers safely;
- do not delete calculated history;
- revert current read model pointer to last validated snapshot when authorized;
- preserve review/action data;
- communicate status to users;
- reconcile queued events before re-enable.

## 24. Production acceptance gate

Required sign-offs:

- business owner;
- Major Projects product owner;
- Crew Hub product owner;
- technical owner;
- security/privacy reviewer;
- data/integration reviewer;
- UAT representatives;
- owner/architecture authority.

No production activation until open decisions in `docs/14_Open_Decisions_and_Production_Gates.md` are closed or explicitly deferred with approved controls.
