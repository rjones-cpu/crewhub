# CH-11 Scoring Policy and Calculation Manual

## 1. Purpose

This manual defines how LodgeX calculates the Company Delivery and Compliance Service Rating. It is the functional source for the deterministic rules engine, policy configuration, calculation trace, test cases and score explanations.

The rules in this document implement the current owner-directed Version 1 baseline. Values identified as **recommended configurable defaults** require project and architecture approval before production use.

## 2. Calculation scope

A rating calculation evaluates:

```text
one Contractor Company
+ one Major Project
+ one policy version
+ one evaluation window
+ one evidence cut-off time
```

### 2.1 Required identifiers

- `tenant_id`;
- `company_id`;
- `project_id`;
- `policy_version_id`;
- `evaluation_window_start`;
- `evaluation_window_end`;
- `evidence_cutoff_at`;
- `calculation_correlation_id`.

### 2.2 Recommended evaluation views

| View | Purpose | Recommended behavior |
|---|---|---|
| Live operational | Current risk and action management | Rolling window, recalculated on events and daily checkpoint |
| Mobilization event | Grade a defined mobilization | Fixed event window |
| Monthly report | Formal project reporting | Locked period snapshot |
| Project lifetime | Historical analysis | Derived from immutable period/event snapshots, not one averaged grade |

**Recommended Version 1 working default:** rolling 30 calendar days for the live operational rating, plus monthly locked reporting snapshots. This remains an owner decision.

## 3. Grade order and presentation

Internally, grade severity is represented as:

```text
A = 1
B = 2
C = 3
D = 4
```

The largest severity number is the worst grade.

| Grade | Colour | Status label |
|---|---|---|
| A | Green | Compliant |
| B | Yellow | On Watch |
| C | Orange | Action Required |
| D | Red | Critical |
| N/A | Gray | Not Applicable |
| Pending Data | Gray/Blue | Insufficient Current Evidence |

Colour is never the only indicator. Every component must display the letter and text label.

## 4. Overall calculation controls

The following controls are mandatory:

1. **Worst applicable criterion wins.**
2. **A requires all applicable criteria to be A.**
3. **N/A criteria are excluded.**
4. **Approved effective-dated exceptions are applied before grade selection.**
5. **Critical overrides force D.**
6. **The same evidence and policy version must always produce the same result.**
7. **Every calculation is versioned and reproducible.**
8. **A recalculation creates a new snapshot; history is not overwritten.**
9. **Incomplete or stale evidence does not automatically create a negative grade.**
10. **AI does not assign the official grade.**

## 5. Source precedence

Each policy identifies approved evidence sources and precedence. A recommended order is:

1. approved project requirement or change record;
2. authoritative Crew Hub company schedule/assignment record;
3. authoritative Smart Lodge or project arrival/check-in record;
4. authoritative CH-07 Journey Management record;
5. authoritative CH-08 LMS/certification record;
6. approved external integration record;
7. dual-verified manual evidence;
8. unverified submission, which cannot establish compliance by itself.

Conflicting authoritative records create a data conflict and review item. The engine must not silently select a lower-quality record.

## 6. Criterion 1 — Workforce Delivery

### 6.1 Business question

Did the company provide the approved number of qualified workers in the required positions, shifts, locations and dates?

### 6.2 Demand units

The project policy must define the unit used for evaluation. Supported units should include:

- headcount at a mobilization checkpoint;
- worker-position assignment;
- worker-shift;
- worker-day;
- worker-position-day.

**Recommended default:** worker-position-day for ongoing work and headcount-by-position for discrete mobilizations.

### 6.3 Valid provided unit

A provided unit counts only when:

- it is linked to the correct project and company;
- it covers the required date/shift/location;
- it fills the required position/trade or an approved equivalent;
- the worker assignment is active and not duplicated;
- any project-required readiness gate for counting delivery is satisfied;
- an approved substitution is recorded where applicable.

Excess workers in one role do not offset shortages in another role. A non-critical excess does not cover a missing critical position.

### 6.4 Measures

For each demand line:

```text
required_units = approved demand units in scope
valid_provided_units = qualifying company-provided units
shortfall_units = max(required_units - valid_provided_units, 0)
excess_units = max(valid_provided_units - required_units, 0)
shortfall_rate = shortfall_units / required_units × 100
absolute_variance_rate = abs(valid_provided_units - required_units) / required_units × 100
```

When `required_units = 0`, the demand line is excluded unless the policy explicitly evaluates unauthorized overstaffing.

### 6.5 Default grade thresholds

| Grade | Default rule |
|---|---|
| A | Absolute approved-scope variance is no more than 5%, all critical positions are covered, and no prolonged shortage exists |
| B | Variance is greater than 5% and no more than 10%, with no critical prolonged shortage |
| C | Variance is greater than 10% and no more than 25%, a material shortage lasts 2–3 days, or B-level shortfalls repeat within the policy window |
| D | Variance is greater than 25%, a critical position is unavailable for more than 3 days, or the failure materially threatens project delivery |

### 6.6 Position-level protection

The engine evaluates:

- overall quantity variance;
- position/trade variance;
- critical position coverage;
- shortage duration;
- repeated events.

The criterion grade is the worst result from these sub-rules.

### 6.7 Approved changes

The following do not count as company under-delivery when approved and effective:

- project-demand reduction;
- date or shift change;
- work-package cancellation;
- project-approved delayed mobilization;
- approved worker substitution;
- provider or project-caused access restriction;
- approved force-majeure exception.

### 6.8 Recommended repeated-event default

**Working recommendation:** two B-level workforce shortfalls within 30 days escalate the criterion to C. This setting is configurable and requires project approval.

## 7. Criterion 2 — Scheduled Arrival Performance

### 7.1 Business question

Did every applicable worker or crew arrive on the approved scheduled date and within the configured arrival window?

### 7.2 Arrival event calculation

For each applicable arrival:

```text
scheduled_arrival_at = approved effective schedule arrival
actual_arrival_at = authoritative actual arrival
lateness = actual_arrival_at - scheduled_arrival_at
lateness_calendar_days = project-time-zone calendar-day difference
```

An approved schedule change replaces the prior scheduled time for scoring, but the prior version remains in history.

### 7.3 Default event grade

| Grade | Default event rule |
|---|---|
| A | Arrived on the scheduled calendar day and within the configured arrival window |
| B | Arrived one calendar day late |
| C | Arrived 2–3 calendar days late, or B-level delays repeat |
| D | Arrived more than 3 calendar days late, was a no-show, or missed a critical mobilization requirement |

### 7.4 Criterion aggregation

The default criterion grade is the worst unexcepted arrival-event grade in the evaluation window. The UI also reports:

- affected worker count;
- affected percentage;
- average and maximum lateness;
- repeated-delay count;
- critical mobilization count.

A project may later approve a percentage-based aggregation, but that change must be versioned and tested. Version 1 should use the worst unexcepted event to remain aligned with the current rating logic.

### 7.5 No-show definition

A no-show occurs when:

- the approved arrival window and grace period pass;
- no authoritative arrival is recorded;
- no approved schedule change or exception exists;
- the worker or company has not provided an accepted replacement or cancellation.

### 7.6 Arrival-source conflict

When Smart Lodge check-in, gate access and supervisor confirmation conflict:

- identify the source precedence defined by policy;
- mark the record Conflict if precedence cannot resolve it;
- do not automatically penalize during unresolved data conflict;
- route the event to authorized review.

## 8. Criterion 3 — Journey Management

### 8.1 Applicability

Journey Management is applicable only when the project policy requires it for the company, worker, travel mode, route, date or risk class.

If no required journeys exist in the evaluation window, the criterion is N/A unless the absence is itself caused by a missing required journey record.

### 8.2 Compliant journey

A journey is compliant when all applicable controls are satisfied, including:

- journey created before the required cutoff;
- required questions completed;
- vehicle and driver requirements valid;
- risk calculated;
- required approval obtained;
- planned route and travel window accepted;
- required check-ins completed;
- overdue escalation resolved;
- journey closed or arrival confirmed;
- no falsified record or unauthorized high-risk travel.

### 8.3 Measures

```text
required_journeys = journeys required by policy in the evaluation window
noncompliant_journeys = required journeys with one or more unexcepted material deficiencies
noncompliance_rate = noncompliant_journeys / required_journeys × 100
```

The policy defines which minor administrative defects are material. A journey with several deficiencies is counted once in the percentage, while all deficiencies remain visible in evidence.

### 8.4 Default grade thresholds

| Grade | Default rule |
|---|---|
| A | All required journeys, approvals, check-ins and closures comply |
| B | One isolated non-critical deficiency or no more than 20% of applicable journeys are noncompliant; no unauthorized high-risk journey |
| C | More than 20% and no more than 40% are noncompliant, or material/repeated failures occur |
| D | More than 40% are noncompliant, or an unauthorized high-risk journey, falsified record or incident linked to noncompliance occurs |

### 8.5 Critical Journey Management conditions

Any configured critical condition forces D regardless of percentage:

- unauthorized high-risk journey;
- deliberate bypass of required approval;
- falsified driver, vehicle, route, check-in or completion record;
- serious incident linked to Journey Management noncompliance;
- ignored emergency escalation;
- other project-approved non-waivable condition.

## 9. Criterion 4 — LMS and Certification Compliance

### 9.1 Applicability

The criterion applies to assigned workers who have one or more project-required courses, certificates, licences or acknowledgements during the evaluation window.

### 9.2 Compliant worker

A worker is compliant when every applicable mandatory requirement:

- is completed;
- is verified;
- is current for the full required assignment period;
- matches the required role, task, location and project;
- is not revoked, invalid or superseded;
- has an approved equivalency or temporary authorization where allowed.

Registration, enrollment or a booked course is not completion.

### 9.3 Measures

```text
applicable_workers = unique assigned workers with one or more applicable requirements
affected_workers = unique applicable workers with one or more unexcepted deficient requirements
compliance_gap_rate = affected_workers / applicable_workers × 100
```

A worker with five missing requirements is counted once in `affected_workers`, but the evidence detail lists all five deficiencies.

### 9.4 Default grade thresholds

| Grade | Default rule |
|---|---|
| A | 100% of applicable assigned workers hold all required current learning and certifications |
| B | Compliance gap is no more than 20% and no critical certification is missing |
| C | Compliance gap is greater than 20% and no more than 40% |
| D | Compliance gap is greater than 40%, a critical certification is missing, evidence is falsified, or noncompliant workers are knowingly mobilized |

### 9.5 Critical conditions

The following force D:

- missing, expired, revoked or invalid critical certification for a worker knowingly mobilized or working;
- falsified training or certification evidence;
- knowing mobilization of noncompliant workers where the requirement is non-waivable;
- serious incident linked to a known training deficiency.

## 10. N/A handling

A criterion is N/A only when:

- the approved project policy does not activate it for the scope; or
- no applicable population exists in the evaluation window; and
- the absence is not itself a compliance failure.

N/A records must include:

- criterion;
- scope;
- reason code;
- policy version;
- effective dates;
- authorizing project record;
- calculation trace.

The UI must distinguish N/A from Missing Data.

## 11. Approved exceptions

An exception is evaluated before the criterion grade.

### 11.1 Exception validity

An exception is valid only when it has:

- authorized approver;
- reason;
- evidence;
- affected criterion;
- company/project/population scope;
- effective start and end;
- policy compatibility;
- no conflict with a non-waivable critical rule;
- active status at the event time.

### 11.2 Exception treatment

The engine may:

- exclude an event from numerator and denominator;
- replace a scheduled date with an approved date;
- apply an approved equivalency;
- exclude a worker or work package from scope;
- modify a threshold only through a versioned policy, not an ad hoc exception.

## 12. Critical overrides

Critical overrides are evaluated after normal criterion calculations and force the affected criterion and overall grade to D.

```text
if any active valid critical_override:
    overall_grade = D
    affected_criterion_grade = D
else:
    overall_grade = max_severity(applicable_criterion_grades)
```

A critical override record must identify:

- rule code;
- evidence;
- affected scope;
- authority;
- start/end or resolution state;
- approval;
- linked corrective actions;
- calculation snapshots affected.

## 13. Data sufficiency and stale evidence

A criterion cannot be fairly calculated when a required authoritative source is unavailable or materially stale.

Possible states:

- Sufficient;
- Sufficient with Manual Evidence;
- Stale;
- Conflicting;
- Insufficient;
- Integration Failed.

Recommended behavior:

1. retain last valid published criterion and overall grade;
2. display Data Stale/Pending Data;
3. block new automatic negative change based solely on missing evidence;
4. create data-quality action;
5. allow authorized manual evidence under controlled workflow;
6. recalculate after restoration.

## 14. Repeated-event escalation

Policy may escalate repeated deficiencies. A repeat rule must define:

- criterion;
- qualifying event level;
- count;
- lookback window;
- same/similar-event classification;
- reset/recovery condition;
- exclusions;
- policy version.

Recommended working defaults:

- two B-level events in the same criterion within 30 days escalate to C;
- an unresolved C beyond its corrective-action due date may escalate to D when policy defines material continuing exposure;
- valid critical events force D immediately.

These defaults require owner approval.

## 15. Calculation order

```text
1. Validate tenant, company, project and permission scope.
2. Load active policy version for the event/evaluation time.
3. Establish evaluation window and evidence cut-off.
4. Load project requirements and approved changes.
5. Load company schedule, assignments and commitments.
6. Load arrival, Journey Management and LMS evidence.
7. Resolve source versions and detect stale/conflicting data.
8. Determine criterion applicability.
9. Apply approved effective-dated exceptions.
10. Calculate criterion measures and sub-rules.
11. Apply repeated-event escalation.
12. Apply critical overrides.
13. Select the worst applicable criterion grade.
14. Create calculation trace and immutable snapshot.
15. Compare with current published snapshot.
16. Publish or hold for review according to policy.
17. Emit domain events and notifications after commit.
```

## 16. Deterministic pseudocode

```php
public function calculate(RatingContext $context): RatingResult
{
    $policy = $this->policies->activeFor(
        projectId: $context->projectId,
        effectiveAt: $context->windowEnd,
    );

    $evidence = $this->evidenceAssembler->assemble($context, $policy);

    if ($evidence->hasMaterialInsufficiency()) {
        return RatingResult::pendingData(
            priorPublishedSnapshot: $this->snapshots->currentPublished($context),
            issues: $evidence->dataIssues(),
        );
    }

    $applicability = $this->applicability->resolve($context, $policy, $evidence);
    $exceptions = $this->exceptions->activeFor($context, $policy);

    $criterionResults = collect([
        $this->workforceEvaluator->evaluate($context, $policy, $evidence, $exceptions),
        $this->arrivalEvaluator->evaluate($context, $policy, $evidence, $exceptions),
        $this->journeyEvaluator->evaluate($context, $policy, $evidence, $exceptions),
        $this->lmsEvaluator->evaluate($context, $policy, $evidence, $exceptions),
    ])->filter(fn (CriterionResult $result) => $result->isApplicable());

    $criterionResults = $this->repeatEscalation->apply(
        context: $context,
        policy: $policy,
        results: $criterionResults,
    );

    $criticalOverrides = $this->criticalOverrides->activeFor($context, $policy, $evidence);

    $overallGrade = $criticalOverrides->isNotEmpty()
        ? Grade::D
        : Grade::worst($criterionResults->pluck('grade'));

    return RatingResult::calculated(
        overallGrade: $overallGrade,
        criterionResults: $criterionResults,
        policyVersion: $policy->version,
        evidenceFingerprint: $evidence->fingerprint(),
        criticalOverrides: $criticalOverrides,
    );
}
```

## 17. Snapshot requirements

A snapshot is immutable and includes:

- rating ID;
- sequence/version;
- overall grade;
- criterion grades;
- policy version;
- evaluation window;
- evidence cut-off;
- calculation status;
- publication status;
- prior snapshot ID;
- superseded-by snapshot ID;
- evidence fingerprint;
- calculation trace JSON;
- data quality state;
- review state;
- created/published actors and times;
- audit correlation ID.

## 18. All Projects company grade

For the Crew Hub All Projects view:

```text
active_project_ratings = current published ratings for active selected projects
all_projects_grade = worst(active_project_ratings)
```

The UI must also show the distribution:

```text
A: count
B: count
C: count
D: count
Pending Data: count
```

Completed/archived projects are excluded from the live grade unless explicitly included.

## 19. Recovery and improvement

The live grade improves only when:

- the evaluation window moves beyond old events;
- authoritative source data is corrected;
- an approved exception applies;
- critical override is validly resolved;
- new compliant evidence changes the applicable measure;
- a policy-defined recovery condition is met.

Closing a corrective action alone does not rewrite the facts. It may remove continuing exposure and support future recovery.

## 20. Rounding and boundaries

- Calculate percentages at full precision.
- Display one decimal place unless the design specifies whole numbers.
- Apply thresholds before display rounding.
- `20.0000%` is B; `20.0001%` is C.
- `40.0000%` is C; `40.0001%` is D.
- Time calculations use the project time zone stored with the policy.
- Calendar-day lateness is based on local project dates, not raw UTC dates.

## 21. Required calculation trace example

```json
{
  "criterion": "lms_certification",
  "applicable": true,
  "policy_version": "1.0-working-default",
  "evaluation_window": {
    "start": "2026-08-01T00:00:00-07:00",
    "end": "2026-08-30T23:59:59-07:00"
  },
  "applicable_workers": 100,
  "affected_workers": 18,
  "compliance_gap_rate": 18.0,
  "critical_certification_missing": false,
  "exceptions_applied": 0,
  "threshold": "greater than 0 and less than or equal to 20 percent",
  "grade": "B",
  "evidence_fingerprint": "sha256:example",
  "source_freshness": "current"
}
```

## 22. Minimum test boundary cases

- exactly 5%, 5.0001%, 10%, 10.0001%, 25%, 25.0001%;
- arrival on time, one day late, two days late, three days late, four days late, no-show;
- Journey rate 0%, 20%, 20.0001%, 40%, 40.0001%;
- LMS gap 0%, 20%, 20.0001%, 40%, 40.0001%;
- N/A;
- zero denominator;
- approved exception;
- expired exception;
- critical override;
- conflicting evidence;
- stale source;
- corrected evidence;
- repeated B escalation;
- All Projects mix containing D.
