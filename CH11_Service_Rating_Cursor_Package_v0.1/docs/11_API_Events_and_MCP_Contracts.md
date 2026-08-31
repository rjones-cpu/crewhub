# CH-11 API, Events and MCP Contracts

## 1. Purpose

This document defines the functional contracts required to connect Crew Hub, Major Projects, arrival sources, Enterprise Core and governed AI/MCP experiences. Exact URI conventions, authentication and schemas must align with the existing LodgeX API standards.

## 2. Contract principles

- APIs call application services, not direct cross-module database writes.
- Every request carries tenant, company/project scope and authenticated actor/service identity.
- Material writes support idempotency.
- Responses include source/version/as-of metadata.
- Events are versioned and replay-safe.
- Protected evidence is summarized or redacted by default.
- AI/MCP inherits user permissions.
- No API or tool permits arbitrary direct grade editing.

## 3. Suggested read APIs

### 3.1 Crew Hub — current rating

```http
GET /api/v1/crew-hub/companies/{companyId}/service-ratings/current?project_id={projectId}
```

Response:

```json
{
  "data": {
    "company_id": "cmp_123",
    "project_id": "prj_456",
    "snapshot_id": "srs_789",
    "overall_grade": "B",
    "status_label": "On Watch",
    "snapshot_status": "published",
    "review_status": null,
    "policy_version": "1.0-working-default",
    "evaluation_window": {
      "start": "2026-08-01T00:00:00-07:00",
      "end": "2026-08-30T23:59:59-07:00"
    },
    "criteria": [
      {
        "code": "workforce_delivery",
        "applicable": true,
        "grade": "A",
        "display_value": "98% provided"
      },
      {
        "code": "scheduled_arrival",
        "applicable": true,
        "grade": "B",
        "display_value": "6 workers arrived 1 day late"
      }
    ],
    "data_quality": "current",
    "calculated_at": "2026-08-15T08:15:00-07:00",
    "published_at": "2026-08-15T08:15:05-07:00"
  }
}
```

### 3.2 Crew Hub — All Projects summary

```http
GET /api/v1/crew-hub/companies/{companyId}/service-ratings/summary
```

Response includes:

- lowest active grade;
- A/B/C/D/Pending counts;
- project rows;
- current filters;
- last updated.

### 3.3 Major Projects — project company register

```http
GET /api/v1/major-projects/{projectId}/service-ratings/companies
```

Query parameters:

- grade;
- criterion;
- company;
- review status;
- corrective-action status;
- data quality;
- period;
- page/sort.

### 3.4 Snapshot detail

```http
GET /api/v1/service-rating-snapshots/{snapshotId}
GET /api/v1/service-rating-snapshots/{snapshotId}/criteria/{criterionCode}
GET /api/v1/service-rating-snapshots/{snapshotId}/history
```

Evidence detail endpoint must apply field-level permissions.

## 4. Suggested write APIs

### 4.1 Request review

```http
POST /api/v1/service-rating-snapshots/{snapshotId}/reviews
Idempotency-Key: <client-generated-key>
```

Request:

```json
{
  "criteria": ["scheduled_arrival"],
  "reason_code": "approved_schedule_change_missing",
  "statement": "The project approved an August 14 arrival date before travel.",
  "requested_treatment": "Apply the approved schedule change and recalculate.",
  "challenged_evidence": [
    {
      "source_type": "arrival_event",
      "source_id": "arr_001",
      "source_version": 3
    }
  ],
  "attachments": ["doc_approved_change_001"],
  "attested": true
}
```

### 4.2 Review decision

```http
POST /api/v1/service-rating-reviews/{reviewId}/decisions
```

Request uses structured outcome:

```json
{
  "decision": "correction_accepted",
  "findings": "The approved schedule change was effective before the original travel date.",
  "source_correction_reference": "chg_123",
  "requires_recalculation": true,
  "co_approval_token": null
}
```

Allowed decisions are controlled. No `new_grade` field exists.

### 4.3 Request/approve exception

```http
POST /api/v1/major-projects/{projectId}/service-rating-exceptions
POST /api/v1/service-rating-exceptions/{exceptionId}/approve
POST /api/v1/service-rating-exceptions/{exceptionId}/deny
```

### 4.4 Apply critical override

```http
POST /api/v1/major-projects/{projectId}/service-rating-critical-overrides
```

Request:

```json
{
  "company_id": "cmp_123",
  "criterion": "journey_management",
  "critical_rule_code": "UNAUTHORIZED_HIGH_RISK_JOURNEY",
  "affected_source_ids": ["jny_888"],
  "evidence_ids": ["doc_critical_888"],
  "effective_at": "2026-08-15T07:30:00-07:00",
  "containment_action": "Suspend additional high-risk travel pending review.",
  "attested": true
}
```

The service forces D according to policy; the request does not contain a grade selector.

### 4.5 Corrective actions

```http
POST /api/v1/service-rating-snapshots/{snapshotId}/corrective-actions
PATCH /api/v1/service-rating-corrective-actions/{actionId}
POST /api/v1/service-rating-corrective-actions/{actionId}/submit-evidence
POST /api/v1/service-rating-corrective-actions/{actionId}/verify
```

## 5. Policy APIs

```http
GET  /api/v1/major-projects/{projectId}/service-rating-policies
POST /api/v1/major-projects/{projectId}/service-rating-policies
GET  /api/v1/service-rating-policies/{policyId}
POST /api/v1/service-rating-policies/{policyId}/validate
POST /api/v1/service-rating-policies/{policyId}/impact-preview
POST /api/v1/service-rating-policies/{policyId}/approve
POST /api/v1/service-rating-policies/{policyId}/schedule
```

An active policy is read only. Changes create a new draft version.

## 6. Manual evidence APIs

```http
POST /api/v1/major-projects/{projectId}/service-rating-manual-evidence
POST /api/v1/service-rating-manual-evidence/{evidenceId}/verify
POST /api/v1/service-rating-manual-evidence/{evidenceId}/supersede
```

Required controls:

- permission;
- source reason;
- attachment/reference;
- verification;
- effective dates;
- data classification;
- audit;
- no grade field.

## 7. Recalculation APIs

Normal recalculation is event/schedule driven. Administrative endpoint:

```http
POST /api/v1/service-ratings/recalculations
```

Request:

```json
{
  "company_id": "cmp_123",
  "project_id": "prj_456",
  "reason_code": "approved_source_correction",
  "source_reference": "review_321",
  "expected_policy_version": "1.0-working-default"
}
```

Access is restricted. It triggers the deterministic service and cannot supply a desired result.

## 8. Domain event envelope

Every event should include:

```json
{
  "event_id": "evt_01J...",
  "event_type": "CompanyServiceRatingChanged",
  "event_version": 1,
  "occurred_at": "2026-08-15T15:15:05Z",
  "producer": "service-rating-domain",
  "tenant_id": "ten_001",
  "organization_id": "org_001",
  "company_id": "cmp_123",
  "project_id": "prj_456",
  "correlation_id": "cor_001",
  "causation_id": "evt_source_001",
  "idempotency_key": "sha256:...",
  "data_classification": "internal_project",
  "payload": {}
}
```

## 9. Core Service Rating events

### 9.1 `CompanyServiceRatingCalculated`

Produced whenever a new calculation snapshot is created.

Payload:

- snapshot ID;
- overall grade;
- criterion grades;
- policy version;
- window;
- data quality;
- evidence fingerprint;
- publication required flag.

### 9.2 `CompanyServiceRatingPublished`

Produced when a snapshot becomes the current published result.

### 9.3 `CompanyServiceRatingChanged`

Produced when published overall grade or a material criterion changes.

Payload includes prior/new snapshot and change drivers.

### 9.4 `ServiceRatingReviewRequested`

Produced when a company submits a review.

### 9.5 `ServiceRatingReviewDecisionPublished`

Produced when review outcome is published.

### 9.6 `ServiceRatingExceptionApproved`

Produced after authorized approval.

### 9.7 `ServiceRatingCriticalOverrideApplied`

Produced after restricted approval; consumers escalate immediately.

### 9.8 `ServiceRatingCriticalOverrideResolved`

Produced after authorized resolution and triggers recalculation.

### 9.9 `ServiceRatingCorrectiveActionUpdated`

Produced on material action state changes.

### 9.10 `ServiceRatingDataQualityChanged`

Produced when a criterion becomes stale, conflicting, insufficient or restored.

## 10. Consumed source events

CH-11 may consume:

- `ProjectWorkforceDemandApproved`;
- `ProjectWorkforceDemandChanged`;
- `ContractorCommitmentUpdated`;
- `CompanySchedulePublished`;
- `WorkerAssignmentChanged`;
- `WorkerCheckedIn`;
- `WorkerNoShowRecorded`;
- `JourneyStatusChanged`;
- `JourneyApprovalChanged`;
- `LmsRequirementStatusChanged`;
- `CertificationStatusChanged`;
- `ApprovedScheduleChangePublished`;
- `ProjectCompanyRelationshipChanged`.

Final event names must be reconciled with the LodgeX event catalogue.

## 11. Retry and replay

- consumers are idempotent;
- event version is validated;
- unknown future versions are quarantined, not misread;
- retries use exponential backoff;
- dead-letter queue includes diagnostic context without protected evidence;
- replay preserves causation and does not duplicate snapshots;
- out-of-order events are handled using source version/effective time;
- reconciliation job compares current source versions after outage.

## 12. MCP resources

Suggested read resources:

```text
lodgex://crew-hub/companies/{company_id}/service-ratings/current
lodgex://crew-hub/companies/{company_id}/service-ratings/projects
lodgex://service-ratings/snapshots/{snapshot_id}
lodgex://major-projects/{project_id}/service-ratings/companies
lodgex://major-projects/{project_id}/service-rating-policy/current
lodgex://service-rating-reviews/{review_id}
lodgex://service-rating-corrective-actions/{action_id}
```

Resource responses are permission-filtered and include as-of/policy metadata.

## 13. MCP tools

### 13.1 Read/explain tools

```text
get_company_service_rating
explain_service_rating
list_service_rating_drivers
list_open_service_rating_reviews
list_service_rating_corrective_actions
preview_service_rating_policy_impact
```

### 13.2 Request/action tools

```text
draft_service_rating_review_request
submit_service_rating_review_request
add_service_rating_review_response
create_service_rating_corrective_action_update
request_service_rating_exception
```

These tools call authenticated application services and may require explicit user confirmation.

### 13.3 Restricted tools

Potential restricted administrative tools:

```text
request_service_rating_recalculation
verify_manual_service_rating_evidence
```

They require strong permission and audit.

### 13.4 Prohibited tools

Do not implement:

```text
set_service_rating_grade
change_company_grade
hide_service_rating
waive_critical_failure
update_rating_snapshot_directly
query_service_rating_mysql_directly
```

## 14. AI explanation response contract

AI explanations should use a structured response:

```json
{
  "snapshot_id": "srs_789",
  "overall_grade": "B",
  "primary_driver": "scheduled_arrival",
  "summary": "Six workers arrived one calendar day late.",
  "facts": [
    {
      "label": "Affected workers",
      "value": 6,
      "evidence_reference": "criterion_result:cr_123"
    }
  ],
  "next_grade_requirements": [
    "No remaining B-level arrival event in the active evaluation window",
    "No new late arrival event"
  ],
  "open_actions": ["sca_001"],
  "confidence": "high",
  "disclaimer": "Explanation only; the official grade is calculated by the deterministic Service Rating service."
}
```

## 15. Error model

Recommended error codes:

```text
SERVICE_RATING_POLICY_NOT_FOUND
SERVICE_RATING_POLICY_NOT_ACTIVE
SERVICE_RATING_DATA_INSUFFICIENT
SERVICE_RATING_EVIDENCE_CONFLICT
SERVICE_RATING_REVIEW_WINDOW_CLOSED
SERVICE_RATING_REVIEW_DUPLICATE
SERVICE_RATING_INVALID_TRANSITION
SERVICE_RATING_EXCEPTION_NON_WAIVABLE
SERVICE_RATING_OVERRIDE_NOT_AUTHORIZED
SERVICE_RATING_SNAPSHOT_IMMUTABLE
SERVICE_RATING_IDEMPOTENCY_CONFLICT
SERVICE_RATING_SCOPE_DENIED
```

Errors do not leak cross-company existence or protected evidence.

## 16. API acceptance criteria

- no routine grade-edit endpoint;
- active policy cannot be edited in place;
- write calls are idempotent where applicable;
- event envelopes are versioned;
- protected evidence is permission-filtered;
- review decision triggers recalculation only through approved underlying changes;
- critical override forces D through domain service;
- events publish after transaction commit;
- MCP inherits user scope;
- API and MCP audit records include correlation IDs.
