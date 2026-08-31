# Major Projects UI Functional Design — Service Rating Governance and Performance

## 1. Design objective

Provide Major Projects with a project-wide contractor performance command centre that makes low grades, disputed scores, policy issues and corrective actions immediately visible while preserving contractor isolation and evidence permissions.

## 2. Navigation

Recommended Major Projects navigation:

```text
Major Projects
├── Dashboard
├── Workforce Demand
├── Workforce Planning
├── Schedule Coordination
├── Readiness & Compliance
├── Accommodations & Transportation
├── Timesheets & Approvals
├── Performance
│   └── Company Service Rating
└── Governance
    ├── Service Rating Policy
    ├── Review Queue
    ├── Exceptions
    ├── Critical Overrides
    ├── Corrective Actions
    └── Audit & Reports
```

## 3. Project Company Performance dashboard

Suggested route:

```text
/major-projects/{project}/performance/service-ratings
```

### 3.1 Header

- project name;
- selected reporting period;
- policy version;
- live/monthly toggle;
- data-as-of indicator;
- refresh;
- export;
- Configure Policy action, permission-based.

### 3.2 Top summary widgets

Recommended order:

1. Total Companies;
2. A — Compliant;
3. B — On Watch;
4. C — Action Required;
5. D — Critical;
6. Reviews Open;
7. Corrective Actions Overdue;
8. Data Stale.

D and overdue critical items receive the strongest visual emphasis.

### 3.3 Rating distribution

Donut or horizontal distribution chart with:

- A/B/C/D counts and percentages;
- Pending Data count shown separately;
- comparison with prior period;
- accessible table alternative.

The chart is descriptive and does not compute an average project grade.

### 3.4 Company performance table

Columns:

- company;
- overall grade;
- workforce grade;
- arrival grade;
- journey grade/N/A;
- LMS grade/N/A;
- trend;
- current driver;
- review status;
- open actions;
- data status;
- policy version;
- last updated;
- actions.

Row actions:

- View Scorecard;
- Open Review;
- Create Corrective Action;
- View History;
- Export Company Report.

### 3.5 Filters

- grade;
- company;
- Prime/contract relationship;
- work package;
- criterion driver;
- review status;
- corrective-action status;
- data freshness;
- policy version;
- date/evaluation window.

## 4. Company scorecard detail

Suggested route:

```text
/major-projects/{project}/companies/{company}/service-rating
```

### 4.1 Header panel

- company name and project relationship;
- overall grade and status;
- previous grade;
- trend;
- evaluation window;
- policy version;
- as-of date/time;
- review status;
- open C/D issues;
- responsible company manager;
- assigned project reviewer.

Primary actions:

- Start/Continue Review;
- Create Corrective Action;
- Request Company Information;
- View Audit;
- Export Scorecard.

No arbitrary Edit Grade action.

### 4.2 Tabs

1. Overview;
2. Workforce;
3. Arrivals;
4. Journey Management;
5. LMS & Certification;
6. Reviews;
7. Exceptions & Overrides;
8. Corrective Actions;
9. History & Audit.

### 4.3 Overview content

- four criterion cards;
- calculation trace summary;
- current grade drivers;
- company explanation/review statement;
- project decisions;
- rating change timeline;
- recovery conditions;
- data quality panel;
- linked mobilizations/work packages.

## 5. Review Queue

Suggested route:

```text
/major-projects/{project}/governance/service-rating-reviews
```

### 5.1 Queue views

- Unassigned;
- Assigned to Me;
- Information Required;
- Due Soon;
- Overdue;
- Critical;
- Completed.

### 5.2 Table columns

- review ID;
- company;
- current grade;
- criteria;
- reason;
- submitted;
- SLA due;
- priority;
- evidence completeness;
- assigned reviewer;
- conflict warning;
- status;
- action.

### 5.3 Review workspace

Three-panel desktop layout:

**Left: Request**

- company statement;
- requested correction;
- affected evidence;
- attachments;
- timeline.

**Centre: Calculation and Evidence**

- policy version;
- criterion calculation;
- threshold;
- source records;
- approved changes;
- evidence conflict;
- data freshness.

**Right: Decision**

- request information;
- confirm;
- accept source correction;
- approve exception;
- deny;
- refer technical defect;
- co-approval status;
- findings and rationale;
- publish decision.

On smaller screens, use tabs or step navigation.

## 6. Service Rating Policy editor

Suggested route:

```text
/major-projects/{project}/governance/service-rating-policy
```

### 6.1 Policy list

Shows:

- version;
- status: Draft, Scheduled, Active, Superseded;
- effective dates;
- created by;
- approved by;
- companies/scope;
- impact summary;
- actions.

### 6.2 Policy editor sections

1. General;
2. Evaluation Window;
3. Workforce Delivery;
4. Scheduled Arrival;
5. Journey Management;
6. LMS & Certification;
7. Repeat Escalation;
8. Critical Rules;
9. Evidence Sources;
10. Review and SLA;
11. Notifications;
12. Publication;
13. Impact Preview;
14. Approval.

### 6.3 Impact preview

Before approval, show:

- companies whose current grade would change;
- number moving up/down;
- criteria affected;
- new N/A scopes;
- threshold boundary impacts;
- open reviews affected;
- historical comparability warning;
- effective date.

Active versions are read only. Edit creates a new draft version.

## 7. Manual evidence entry

Suggested modal/page:

```text
/major-projects/{project}/service-rating/manual-evidence/create
```

Fields:

- company;
- criterion;
- evidence type;
- affected date/window;
- work package;
- workers/population;
- measured fact;
- source organization;
- source reference;
- attachment;
- data classification;
- reason manual entry is required;
- verifier;
- expiry/supersession;
- attestation.

The UI previews how the fact will be used but does not let the entrant choose the grade.

## 8. Exception workspace

Suggested route:

```text
/major-projects/{project}/governance/service-rating-exceptions
```

### 8.1 List columns

- exception ID;
- company;
- criterion;
- scope;
- reason;
- effective start/end;
- status;
- requester;
- approver;
- expiry;
- affected rating;
- action.

### 8.2 Approval form

- request summary;
- evidence;
- policy compatibility;
- non-waivable rule check;
- scope selection;
- effective dates;
- retroactive justification;
- decision;
- conditions;
- co-approval;
- recalculation preview.

## 9. Critical Override workspace

Suggested route:

```text
/major-projects/{project}/governance/service-rating-critical-overrides
```

The list and form use red critical styling but remain readable and accessible.

Required UI safeguards:

- restricted role gate;
- prominent “Forces D” warning;
- critical rule code;
- evidence required;
- affected company/criterion/population;
- confirmation step;
- optional dual approval;
- immediate notification preview;
- continuation/restart action;
- resolution controls separate from creation.

No user can force A, B or C through this screen.

## 10. Corrective Actions dashboard

Suggested route:

```text
/major-projects/{project}/governance/service-rating-corrective-actions
```

### 10.1 Widgets

- open actions;
- C actions;
- D actions;
- overdue;
- awaiting company plan;
- awaiting project verification;
- reopened;
- average cycle time.

### 10.2 Table

- action ID;
- company;
- criterion;
- source grade;
- issue;
- owner;
- due date;
- status;
- project verifier;
- effectiveness check;
- action.

## 11. History and reporting

### 11.1 Company history

- timeline;
- snapshot table;
- policy version markers;
- review/exception/override markers;
- compare snapshots;
- export reproducible calculation package.

### 11.2 Project reporting

Reports:

- company rating register;
- rating distribution;
- criterion heatmap;
- declining companies;
- repeated B/C events;
- critical D register;
- review SLA report;
- exception report;
- corrective-action report;
- data-quality/freshness report;
- monthly locked scorecard package.

## 12. Executive view

Executives should see:

- rating distribution;
- number of D companies;
- companies declining;
- project delivery exposure;
- critical criterion drivers;
- overdue corrective actions;
- unresolved reviews;
- trend by month;
- forecast risk.

They should not automatically see protected worker-level evidence.

## 13. UI status patterns

### Published

Standard grade presentation with as-of time.

### Under Review

Grade remains visible. Add blue/neutral `Under Review` status and review due date.

### Corrected/Superseded

Show current snapshot and link to superseded prior version.

### Data Stale

Neutral status, last sync time, source, affected criterion and data-quality action. Do not present stale as noncompliance.

### Provisional Critical

Red status with explicit `Provisional — Verification Required` wording and immediate action owner.

### N/A

Gray criterion card with reason and effective scope.

## 14. Responsive design

- desktop: full table and multi-panel review workspace;
- tablet: summary cards plus condensed table; review workspace becomes two-step layout;
- mobile: ratings and critical queue first; complex policy editing remains desktop-preferred with responsive read-only views;
- sticky actions for review decisions;
- confirmation modals for consequential decisions.

## 15. Accessibility

- grade letter and label plus colour;
- accessible table headers and captions;
- charts have data alternatives;
- keyboard-operable policy editor and review workspace;
- warning/error summaries;
- confirmation text for exceptions and critical overrides;
- adequate colour contrast;
- no hidden critical evidence on hover only;
- screen-reader announcement when a decision changes recalculation status.

## 16. Major Projects UI acceptance criteria

- Project dashboard shows A/B/C/D distribution and critical counts.
- D receives stronger priority than aggregate percentage.
- Company detail explains every criterion.
- Reviewer cannot select an arbitrary replacement grade.
- Policy versions are immutable after activation.
- Manual evidence captures facts and verification, not grade.
- Exceptions are scoped and effective-dated.
- Critical overrides only force D and require restricted authority.
- Data-stale status is separate from poor performance.
- cross-company evidence is isolated.
- every review and decision is auditable.
