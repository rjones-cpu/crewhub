# CH-11 Technical Implementation Architecture

## 1. Purpose

This document translates the Service Rating operating model into a Laravel, React/Inertia, Tailwind and MySQL implementation approach. Cursor must inspect and follow the existing repository conventions before creating files. Names below are a recommended target structure, not permission to duplicate existing services.

## 2. Architecture style

Implement CH-11 as a bounded domain inside the existing LodgeX application architecture:

- deterministic domain services;
- application-service boundaries between Crew Hub and Major Projects;
- immutable rating snapshots;
- versioned project policies;
- event-driven recalculation;
- asynchronous notifications and read-model updates;
- Laravel policies and scoped repositories;
- React/Inertia pages using shared Service Rating components;
- no direct AI or MCP access to production tables;
- no direct cross-module table writes.

## 3. Suggested backend namespace

```text
app/Domain/ServiceRating/
├── Actions/
│   ├── CalculateCompanyProjectRating.php
│   ├── PublishServiceRatingSnapshot.php
│   ├── RequestServiceRatingReview.php
│   ├── ResolveServiceRatingReview.php
│   ├── ApproveServiceRatingException.php
│   ├── ApplyCriticalServiceRatingOverride.php
│   ├── ResolveCriticalServiceRatingOverride.php
│   ├── CreateServiceRatingCorrectiveAction.php
│   └── RecalculateAffectedRatings.php
├── Contracts/
│   ├── RatingEvidenceProvider.php
│   ├── RatingPolicyRepository.php
│   ├── RatingSnapshotRepository.php
│   └── RatingPublisher.php
├── DTOs/
│   ├── RatingContextData.php
│   ├── WorkforceEvidenceData.php
│   ├── ArrivalEvidenceData.php
│   ├── JourneyEvidenceData.php
│   ├── LmsEvidenceData.php
│   ├── CriterionResultData.php
│   └── RatingResultData.php
├── Enums/
│   ├── Grade.php
│   ├── CriterionCode.php
│   ├── RatingSnapshotStatus.php
│   ├── ReviewStatus.php
│   ├── DataQualityStatus.php
│   ├── CorrectiveActionStatus.php
│   └── EvidenceSourceType.php
├── Evaluators/
│   ├── WorkforceDeliveryEvaluator.php
│   ├── ScheduledArrivalEvaluator.php
│   ├── JourneyManagementEvaluator.php
│   └── LmsCertificationEvaluator.php
├── Events/
├── Exceptions/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
├── Queries/
├── Repositories/
├── Rules/
├── Services/
└── ValueObjects/
```

Adapt the structure to the current repository’s domain/module organization.

## 4. Core domain objects

### 4.1 ServiceRatingPolicy

Represents project-approved rules. Important fields:

- project;
- version;
- status;
- effective start/end;
- evaluation window definition;
- criterion activation;
- thresholds;
- critical rules;
- source precedence;
- repeat escalation;
- review SLAs;
- publication rules;
- approval metadata.

Active versions are immutable.

### 4.2 ServiceRatingSnapshot

Immutable calculated result. Important fields:

- company/project/policy;
- evaluation window;
- overall grade;
- status;
- data quality;
- prior/superseding snapshot;
- evidence fingerprint;
- calculation trace;
- publication metadata;
- review state.

### 4.3 CriterionResult

One record per criterion per snapshot:

- applicability;
- grade;
- numerator/denominator;
- measured value;
- threshold;
- drivers;
- exception/override flags;
- evidence freshness;
- explanation code.

### 4.4 EvidenceLink

A stable reference to an authoritative source record:

- source product/service;
- source record type and ID;
- source version;
- effective timestamp;
- evidence classification;
- hash/fingerprint where appropriate;
- display summary;
- permission scope.

Avoid copying entire source records into the rating domain unless required for immutable legal/audit evidence. Prefer versioned references plus necessary calculation facts.

### 4.5 ReviewRequest

- snapshot;
- criteria;
- reason codes;
- challenged evidence;
- company statement;
- attachments;
- assignment;
- SLA;
- decision;
- resulting snapshot.

### 4.6 Exception and CriticalOverride

Both are scoped and effective-dated. Critical overrides are restricted and force D.

### 4.7 CorrectiveAction

Shared company/project workflow with company ownership, project verification and rating linkage.

## 5. Grade enum

Recommended PHP enum:

```php
<?php

declare(strict_types=1);

namespace App\Domain\ServiceRating\Enums;

enum Grade: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';

    public function severity(): int
    {
        return match ($this) {
            self::A => 1,
            self::B => 2,
            self::C => 3,
            self::D => 4,
        };
    }

    /** @param iterable<self> $grades */
    public static function worst(iterable $grades): self
    {
        $worst = self::A;

        foreach ($grades as $grade) {
            if ($grade->severity() > $worst->severity()) {
                $worst = $grade;
            }
        }

        return $worst;
    }
}
```

N/A and Pending Data are criterion/calculation states, not A–D grades.

## 6. Evidence providers

Create adapters that retrieve evidence through owning application services:

```text
MajorProjectsWorkforceDemandEvidenceProvider
CrewHubScheduleEvidenceProvider
ArrivalEvidenceProvider
CrewHubJourneyEvidenceProvider
CrewHubLmsEvidenceProvider
ServiceRatingExceptionProvider
CriticalOverrideProvider
```

Each provider returns typed DTOs and source metadata. It must not expose raw unrelated product records.

## 7. Calculation service

`CalculateCompanyProjectRating` should:

1. validate scope and policy;
2. assemble evidence;
3. evaluate data sufficiency;
4. determine applicability;
5. apply exceptions;
6. call four criterion evaluators;
7. apply repeated-event escalation;
8. apply critical overrides;
9. select worst grade;
10. build calculation trace;
11. persist immutable snapshot and criterion results in a transaction;
12. create outbox events;
13. publish after commit.

Use value objects for percentages, evaluation windows and source references to avoid inconsistent arithmetic and time-zone handling.

## 8. Idempotency and concurrency

A calculation request must include or derive an idempotency key such as:

```text
sha256(company_id|project_id|policy_version|window_start|window_end|evidence_fingerprint)
```

Controls:

- unique index on calculation/idempotency key;
- transaction-level locking or optimistic version check for current published pointer;
- no duplicate snapshot for identical evidence;
- events use outbox pattern or existing reliable event mechanism;
- concurrent recalculations coalesce or safely produce one current result;
- publication pointer update is atomic.

## 9. Snapshot immutability

Prohibit updates to calculation fields after creation. Permitted mutable metadata should be limited to fields such as publication status where architecture permits, and preferably represented through separate lifecycle records.

Recommended approach:

- snapshot row immutable;
- publication record separate or append-only;
- review state derived from review records;
- supersession links added through controlled service and audit;
- database/model guards prevent normal update/delete;
- administrative correction creates a new snapshot.

## 10. Recalculation orchestration

### 10.1 Event-driven triggers

Listeners dispatch a unique calculation job for affected company/project/window.

Examples:

- `ProjectWorkforceDemandApproved`;
- `CompanySchedulePublished`;
- `WorkerAssignmentChanged`;
- `WorkerCheckedIn`;
- `WorkerNoShowRecorded`;
- `JourneyStatusChanged`;
- `LmsRequirementStatusChanged`;
- `ServiceRatingExceptionApproved`;
- `ServiceRatingCriticalOverrideApplied`.

### 10.2 Scheduled checkpoint

A scheduled job identifies active company-project relationships and recalculates when:

- evaluation window moved;
- evidence changed;
- exception expired;
- stale source recovered;
- policy effective date changed;
- repeat-event state changed.

Do not recalculate every record blindly when an evidence fingerprint shows no change.

## 11. Publication strategy

Implement policy-controlled publication:

- automatic;
- review-before-publish for C/D;
- immediate provisional critical D;
- monthly locked snapshot.

Publication emits events and updates Crew Hub/Major Projects read models.

## 12. Review workflow implementation

Use application actions and state transition validation:

```text
Draft → Submitted → Review Requested → Under Review
→ Information Required ↔ Under Review
→ Confirmed / Correction Accepted / Exception Approved / Denied / Withdrawn
→ Closed
```

Every transition verifies:

- current state;
- actor permission;
- assignment;
- required fields;
- co-approval;
- due date/escalation;
- linked snapshot version.

## 13. Authorization implementation

Create Laravel policies such as:

```text
ServiceRatingSnapshotPolicy
ServiceRatingPolicyVersionPolicy
ServiceRatingReviewPolicy
ServiceRatingExceptionPolicy
ServiceRatingCriticalOverridePolicy
ServiceRatingCorrectiveActionPolicy
ServiceRatingEvidencePolicy
```

Use policy checks in controllers/actions and scoped query services. Do not rely on frontend hiding.

## 14. Suggested controllers/routes

### Crew Hub

```text
CrewHub\ServiceRatingDashboardController
CrewHub\ServiceRatingShowController
CrewHub\ServiceRatingEvidenceController
CrewHub\ServiceRatingReviewController
CrewHub\ServiceRatingCorrectiveActionController
CrewHub\ServiceRatingHistoryController
```

### Major Projects

```text
MajorProjects\ServiceRatingDashboardController
MajorProjects\CompanyServiceRatingController
MajorProjects\ServiceRatingPolicyController
MajorProjects\ServiceRatingReviewQueueController
MajorProjects\ServiceRatingExceptionController
MajorProjects\ServiceRatingCriticalOverrideController
MajorProjects\ServiceRatingCorrectiveActionController
MajorProjects\ServiceRatingReportController
```

Prefer thin controllers that call authorized application actions and query objects.

## 15. Frontend structure

Suggested shared structure:

```text
resources/js/Components/ServiceRating/
├── GradeBadge.tsx
├── OverallRatingWidget.tsx
├── RatingStatusPill.tsx
├── CriterionScoreCard.tsx
├── CriterionScoreRow.tsx
├── DataFreshnessIndicator.tsx
├── EvidenceDrawer.tsx
├── PolicyVersionBadge.tsx
├── RatingDistributionDonut.tsx
├── ReviewStatusTimeline.tsx
├── CorrectiveActionPanel.tsx
├── CalculationTracePanel.tsx
└── RatingHistoryTimeline.tsx
```

Pages:

```text
resources/js/Pages/CrewHub/ServiceRatings/
resources/js/Pages/MajorProjects/ServiceRatings/
```

Follow existing TypeScript/JavaScript conventions. Use generated or shared DTO types where the repo supports them.

## 16. Inertia page data

Avoid sending unrestricted evidence to the browser. Page props should contain only authorized, display-ready data.

Example:

```ts
export interface ServiceRatingPageProps {
  scope: {
    companyId: string;
    projectId: string | null;
    allProjects: boolean;
  };
  currentRating: RatingSummary | null;
  criteria: CriterionSummary[];
  distribution?: RatingDistribution;
  permissions: ServiceRatingPermissions;
  dataQuality: DataQualitySummary;
  openReviews: ReviewSummary[];
  openActions: CorrectiveActionSummary[];
}
```

## 17. Design tokens

Map grade tokens into the existing Tailwind theme rather than scattering literal colours.

Suggested semantic tokens:

```text
rating-a-bg / rating-a-text / rating-a-border
rating-b-bg / rating-b-text / rating-b-border
rating-c-bg / rating-c-text / rating-c-border
rating-d-bg / rating-d-text / rating-d-border
rating-na-bg / rating-na-text / rating-na-border
rating-pending-bg / rating-pending-text / rating-pending-border
```

B must be yellow consistently.

## 18. Feature flags and rollout

Use feature flags/configuration for:

- Service Rating module enabled;
- project activation;
- automatic publication;
- manual evidence;
- reviews;
- critical overrides;
- corrective actions;
- AI explanation;
- MCP read tools;
- MCP write/request tools;
- monthly locked reporting.

Rollout sequence:

1. internal development;
2. fixture/demo mode;
3. shadow calculation without user visibility;
4. compare against manual expected results;
5. pilot project read-only;
6. enable review workflow;
7. enable official publication;
8. enable governed AI/MCP support.

## 19. Observability

Metrics:

- calculation duration;
- calculation failures;
- duplicate/idempotent skips;
- data-stale count;
- event queue age;
- publication lag;
- review SLA breach;
- corrective-action overdue count;
- policy activation failures;
- snapshot/evidence mismatch;
- authorization denials;
- manual evidence rate.

Logs/traces include correlation ID, tenant-safe scope, policy version, evidence fingerprint and result. Do not log sensitive evidence contents.

## 20. Security controls

- authorization at every service boundary;
- company/project isolation;
- field-level evidence minimization;
- encryption in transit and at rest;
- secure attachment storage;
- malware scanning where available;
- immutable audit;
- rate limiting on exports/recalculation requests;
- CSRF/session controls for UI actions;
- signed/expiring URLs for attachments;
- strong confirmation for critical overrides;
- no production data in lower environments without protection.

## 21. AI implementation

AI support should consume a sanitized `RatingExplanationContext` generated by the deterministic service:

```text
- current grade and criterion grades;
- policy rules applied;
- summarized evidence facts;
- approved exceptions;
- open corrective actions;
- forecast risk inputs;
- user permission scope.
```

AI output is stored as an explanation/recommendation with model/prompt version and evidence references. It is never the official calculation trace.

## 22. MCP implementation

MCP resources may expose authorized read-only scorecard data. Write-capable tools should create requests or actions through application services and approval workflows, not directly modify grades.

Examples:

- read company rating;
- explain calculation;
- list open corrective actions;
- draft review request;
- submit review request with user confirmation;
- add corrective-action update;
- request exception review.

Prohibit a tool named `set_grade` or equivalent.

## 23. Performance targets — proposed

Initial proposed targets, subject to production SLO approval:

- one company/project calculation: under 5 seconds for typical windows;
- project dashboard read: under 2 seconds at p95 with read models/cache;
- event-to-rating update: within 15 minutes or configured service level;
- critical alert dispatch: within 1 minute after verified critical publication;
- review submission: synchronous confirmation under 3 seconds, attachment processing asynchronous;
- deterministic structured output validity: 100%.

## 24. Engineering completion gate

The implementation is not complete until:

- policy and snapshots are versioned;
- all four evaluators pass boundary tests;
- All Projects worst-grade rule passes;
- N/A and Pending Data are distinct;
- exceptions and critical overrides are authorized and auditable;
- source corrections create new snapshots;
- reviews cannot directly edit grades;
- cross-company authorization tests pass;
- event processing is idempotent;
- UI includes loading, stale, conflict and error states;
- AI/MCP cannot bypass the domain service;
- release has rollback and monitoring plans.
