# Crew Hub UI Functional Design — CH-01 and CH-11 Service Rating

## 1. Design objective

Make the company’s Service Rating one of the most visible and actionable elements in Crew Hub Company Command while preserving the operational dashboard context. Users should understand the current grade in seconds, identify the criterion causing it, and move directly to the source workflow, review request or corrective action.

## 2. Visual language

| Grade | Token name | Visual treatment |
|---|---|---|
| A | `rating-a` | Green badge, green status accent, “Compliant” |
| B | `rating-b` | Yellow badge, yellow status accent, “On Watch” |
| C | `rating-c` | Orange badge, orange status accent, “Action Required” |
| D | `rating-d` | Red badge, red status accent, “Critical” |
| N/A | `rating-na` | Gray badge, “Not Applicable” |
| Pending Data | `rating-pending` | Neutral blue/gray badge, “Pending Data” |

Use letter, label and icon; never rely on colour alone. Grade circles must maintain accessible contrast.

## 3. Company Command page

### 3.1 Desktop layout

Preserve the current dark-navy LodgeX left navigation and white card-based workspace.

Top row widget order:

1. Overall Company Rating;
2. Major Projects;
3. Total Workers;
4. Ready Workforce;
5. Journeys Due Next 48h — bus icon;
6. Accommodation Status — `100% Reservations Confirmed` example;
7. Timesheets & Approvals;
8. Projects at Risk.

### 3.2 Overall Company Rating widget

Minimum width should be larger than standard KPI widgets where the responsive grid permits.

Content:

- label: **Overall Company Rating**;
- grade circle;
- status title, e.g. Good;
- status subtitle, e.g. On Watch;
- selected scope indicator: All Projects or project name;
- Under Review badge when applicable;
- Data Stale indicator when applicable;
- clickable body or `View Scorecard` action.

Tooltip/help text:

> The displayed grade is the lowest active applicable project rating in All Projects view. Select a project to view that project’s rating.

### 3.3 Required correction to current mockup

The reference image shows project grades A, B, B, C and D while the top grade shows B. The production UI must show D for that All Projects sample. B is valid only when:

- a B-rated project is selected; or
- the D project is outside the active filter; or
- the D snapshot is not published/current and this is explicitly shown.

### 3.4 Primary dashboard cards

Row two:

- Company Performance Scorecard;
- Workforce Outlook (14 Days);
- Scorecard Summary.

Row three:

- Company Performance by Project;
- Top Priority Actions;
- Upcoming Mobilizations.

Bottom alert strip:

- number of projects needing attention;
- critical/declining description;
- `View Critical Items` action.

## 4. Company Performance Scorecard card

### 4.1 Header

- title;
- help icon;
- selected project/all-project scope;
- policy version tooltip;
- as-of timestamp.

### 4.2 Grade summary

- large grade badge;
- status title;
- status label;
- next review date;
- previous grade indicator;
- open review indicator.

### 4.3 Criterion rows

Each row includes:

- criterion number and name;
- measured value/short explanation;
- grade badge;
- warning or exception icon;
- clickable row.

Example:

```text
1. Required Workforce Provided       98% compliant       A
2. Workforce Arrival                 6 workers 1 day late B
3. Journey Management                100% compliant      A
4. LMS & Certification               100% compliant      A
```

Footer action: `View Scorecard Details`.

## 5. Service Rating overview page

Suggested route:

```text
/crew-hub/service-ratings
```

### 5.1 Header controls

- project selector;
- date/evaluation window selector;
- current/history toggle;
- export button, permission-based;
- help link;
- data freshness indicator.

### 5.2 Hero panel

Displays:

- current grade;
- current status;
- previous grade;
- trend;
- snapshot status;
- policy version;
- evaluation window;
- as-of time;
- next recalculation;
- `Request Review` action;
- `View Calculation` action.

### 5.3 Criterion grid

Four equal cards on desktop, stacked on mobile.

Each card:

- icon;
- criterion name;
- grade;
- measured value;
- threshold band visualization;
- affected count;
- evidence freshness;
- applied exception count;
- `View Details`;
- `Fix Source` where company-owned;
- forecast risk indicator.

### 5.4 Requirements to improve panel

Plain-language system-generated deterministic guidance:

- “Resolve 6 one-day late arrival events or wait until they leave the active evaluation window.”
- “Verify LMS completion for 3 workers to return the LMS criterion to A.”

AI may improve wording but must cite the deterministic calculation facts and be labeled AI-assisted.

## 6. Project scorecard detail page

Suggested route:

```text
/crew-hub/service-ratings/{project}
```

Tabs:

1. Overview;
2. Workforce;
3. Arrivals;
4. Journey Management;
5. LMS & Certifications;
6. Reviews;
7. Corrective Actions;
8. History.

### 6.1 Overview tab

- grade header;
- four criterion summary rows;
- open actions;
- rating change timeline;
- forecast risk;
- policy summary;
- review status.

### 6.2 Workforce tab

Table columns:

- demand item;
- work package;
- position/trade;
- critical flag;
- required units;
- committed units;
- valid provided units;
- shortfall/excess;
- duration;
- grade driver;
- source status;
- action.

Actions:

- view project demand;
- view company assignments;
- correct assignment;
- request project review;
- link approved change.

### 6.3 Arrivals tab

Columns:

- worker/crew;
- scheduled arrival;
- actual arrival;
- lateness;
- source;
- event grade;
- exception;
- status;
- action.

Filters: on time, one day late, 2–3 days late, >3 days/no-show, exception, conflict.

### 6.4 Journey tab

Summary widgets:

- journeys required;
- compliant;
- noncompliant;
- noncompliance rate;
- high-risk unauthorized;
- missed check-ins.

Table uses restricted detail based on role.

### 6.5 LMS tab

Summary widgets:

- applicable workers;
- fully compliant;
- affected workers;
- compliance gap;
- critical deficiencies;
- pending verification.

Table columns:

- worker;
- role;
- requirement;
- status;
- completion;
- expiry;
- critical;
- verifier;
- action.

## 7. Evidence drawer

Selecting a calculation line opens a right-side drawer.

Sections:

- calculation result;
- threshold applied;
- evidence source;
- source version and timestamp;
- affected record;
- approved changes;
- exception/override;
- audit link;
- available actions.

Actions are role-scoped:

- open source record;
- correct source;
- submit evidence;
- request review;
- create corrective action.

## 8. Review request wizard

Suggested route/modal:

```text
/crew-hub/service-ratings/{snapshot}/reviews/create
```

Seven-step stepper:

1. Rating;
2. Criteria;
3. Reason;
4. Evidence challenged;
5. Explanation;
6. Attachments;
7. Review and submit.

Show a persistent sidebar:

- current grade;
- affected criterion;
- submission deadline;
- project review SLA;
- privacy reminder.

After submission, show a confirmation page with request ID, assigned queue, due date and next steps.

## 9. Reviews page

Suggested route:

```text
/crew-hub/service-ratings/reviews
```

Columns:

- request ID;
- project;
- rating/snapshot;
- criteria;
- reason;
- submitted;
- status;
- reviewer/queue;
- due date;
- action.

Review detail includes a conversation timeline, evidence list, decision and resulting snapshot.

## 10. Corrective Actions workspace

Suggested route:

```text
/crew-hub/service-ratings/corrective-actions
```

Views:

- My Actions;
- All Company Actions;
- Overdue;
- Awaiting Project Verification;
- Closed.

Action detail uses sections:

- issue and rating impact;
- containment;
- root cause;
- action plan;
- owner/due date;
- evidence;
- project verification;
- effectiveness check;
- history.

## 11. Rating history page

Visual components:

- timeline of grade changes;
- criterion trend lines;
- snapshot table;
- policy-version markers;
- exception and review markers;
- compare snapshots control.

Comparison view shows old and new values side by side and highlights changed evidence, thresholds and decisions.

## 12. Scorecard Summary donut

The donut chart in All Projects view displays the number and percentage of active projects at each grade. It is not used to calculate the overall grade.

Accessible alternative: table/list with counts and percentages.

## 13. Priority actions integration

Priority Actions should include rating-driven issues alongside other Company Command actions.

Examples:

- workers arrived one day late;
- schedule gap in a critical role;
- journeys due next 48 hours;
- LMS certification expiring;
- score review response due;
- corrective action overdue;
- data source stale.

Each row links to the source workflow rather than only the scorecard.

## 14. Responsive behavior

### Desktop

- full eight-widget KPI row;
- three-column dashboard cards;
- tables with fixed critical columns and horizontal scroll where needed.

### Tablet

- top widgets wrap to two rows;
- scorecard card full width;
- chart and summary split;
- tables use condensed columns.

### Mobile

- rating widget first;
- project selector sticky near top;
- criterion cards stacked;
- evidence shown in full-screen sheet;
- actions presented in a bottom action bar;
- complex tables switch to record cards.

## 15. Required UI states

Every screen must design:

- loading skeleton;
- empty/no active projects;
- N/A criterion;
- no evidence;
- data stale;
- integration failure;
- conflicting evidence;
- review requested;
- information required;
- corrected/superseded;
- permission denied;
- archived project;
- export generating/failed.

## 16. Accessibility

- keyboard navigation;
- visible focus states;
- semantic headings and tables;
- screen-reader labels for grade and trend;
- letter/text plus colour;
- chart data table alternative;
- sufficient contrast;
- no critical action available only on hover;
- accessible error summaries in forms;
- confirmation dialogs for consequential submissions.

## 17. Component mapping

Use shared components described in `ui/component_catalogue.md`, including:

- `OverallRatingWidget`;
- `GradeBadge`;
- `RatingStatusPill`;
- `CriterionScoreCard`;
- `EvidenceDrawer`;
- `ReviewStatusTimeline`;
- `CorrectiveActionPanel`;
- `PolicyVersionBadge`;
- `DataFreshnessIndicator`;
- `RatingDistributionDonut`;
- `CompanyProjectRatingTable`.

## 18. Crew Hub UI acceptance criteria

- Overall Grade is the first major KPI widget.
- B is yellow in every Crew Hub component.
- All Projects grade uses the lowest active project grade.
- Selected-project grade uses the selected project only.
- A D project cannot be hidden by the donut or average.
- Every criterion is drillable to evidence.
- Company-owned source corrections open the owning workflow.
- Request Review captures structured reason and evidence.
- Current grade remains visible during review.
- Data stale is distinct from a failing grade.
- protected evidence is role-scoped.
- all historical snapshots remain accessible to authorized users.
