# CH-11 Service Rating UI Component Catalogue

## Shared components

### `GradeBadge`

Props:

```ts
type Grade = 'A' | 'B' | 'C' | 'D';

interface GradeBadgeProps {
  grade: Grade;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  showLabel?: boolean;
  statusLabel?: string;
  ariaLabel?: string;
}
```

Rules:

- A green, B yellow, C orange, D red;
- letter always visible;
- accessible label;
- no literal colour logic duplicated across pages.

### `OverallRatingWidget`

Displays grade, status, scope, review/data status and link to detail. Supports selected project and All Projects.

### `CriterionScoreCard`

Displays criterion grade, measure, threshold, affected count, freshness and actions.

### `CriterionScoreRow`

Compact scorecard row for dashboard cards and tables.

### `RatingStatusPill`

Values:

- Calculated;
- Provisional;
- Published;
- Under Review;
- Confirmed;
- Corrected;
- Superseded;
- Data Stale;
- Pending Data.

### `DataFreshnessIndicator`

Shows source, current/stale/conflicting status and last synchronization.

### `PolicyVersionBadge`

Shows policy version and effective period; click opens read-only summary when authorized.

### `EvidenceDrawer`

Right-side desktop drawer/full-screen mobile sheet. Must receive already authorized display data.

### `CalculationTracePanel`

Shows formula facts and threshold in business language, with optional technical JSON for administrators.

### `RatingDistributionDonut`

Descriptive A/B/C/D distribution with accessible table. Never used to calculate overall grade.

### `CompanyProjectRatingTable`

Supports grade cells, trend, review/action status, freshness and drill-down.

### `ReviewStatusTimeline`

Append-only review events.

### `ReviewDecisionPanel`

Structured decisions; no replacement-grade selector.

### `ExceptionScopeBuilder`

Select company, criterion, population/work package and effective dates; validates non-waivable rules.

### `CriticalOverridePanel`

Restricted component with “Forces D” warning, evidence, dual approval and resolution state.

### `CorrectiveActionPanel`

Issue, containment, root cause, actions, ownership, due dates, evidence and verification.

### `RatingHistoryTimeline`

Displays grade changes, policy markers, reviews, exceptions and supersession.

### `SnapshotCompareView`

Side-by-side old/new measures, evidence, rules and outcomes.

## Form components

- `ReviewReasonSelect`;
- `EvidenceReferencePicker`;
- `SecureAttachmentUploader`;
- `ProjectPolicySelector`;
- `EvaluationWindowPicker`;
- `CriterionApplicabilityControl`;
- `ThresholdEditor`;
- `CriticalRuleEditor`;
- `SourcePrecedenceEditor`;
- `ApprovalChainDisplay`;
- `AttestationCheckbox`;
- `ConsequentialActionConfirmDialog`.

## Chart components

- `WorkforceOutlookChart` — existing Company Command design;
- `CriterionTrendChart`;
- `RatingDistributionDonut`;
- `CompanyRatingHeatmap`;
- `CorrectiveActionAgingChart`.

Every chart needs an accessible table/list alternative.

## Semantic token requirements

```text
--rating-a-bg
--rating-a-text
--rating-a-border
--rating-b-bg
--rating-b-text
--rating-b-border
--rating-c-bg
--rating-c-text
--rating-c-border
--rating-d-bg
--rating-d-text
--rating-d-border
--rating-na-bg
--rating-pending-bg
```

Map tokens to the LodgeX Tailwind theme and existing design system.
