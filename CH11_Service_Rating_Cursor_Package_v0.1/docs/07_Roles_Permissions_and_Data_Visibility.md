# CH-11 Roles, Permissions and Data Visibility

## 1. Purpose

This document defines the minimum authorization model for Service Rating. It is a functional permission specification to be translated into LodgeX RBAC/ABAC policies, Laravel authorization policies, query scopes, field-level visibility and audit tests.

## 2. Authorization dimensions

Every request must evaluate applicable dimensions:

- tenant;
- organization/company;
- project;
- work package;
- user role;
- functional responsibility;
- approval authority;
- evidence classification;
- worker relationship;
- effective date;
- purpose of access;
- review/action assignment.

A role name alone is not sufficient. A Project Manager on Project A does not automatically have access to Project B or unrestricted company evidence.

## 3. Core roles

### 3.1 Crew Hub roles

- Company Owner;
- Company Administrator;
- Workforce Manager;
- Scheduler;
- HSE/Compliance Manager;
- Journey Manager;
- LMS/Training Administrator;
- Supervisor;
- Time Approver;
- Worker;
- Company Executive Read Only.

### 3.2 Major Projects roles

- Project Client Administrator;
- Major Project Prime Administrator;
- Project Manager;
- Workforce Planner;
- Schedule Coordinator;
- HSE/Compliance Reviewer;
- Contract/Procurement Manager;
- Service Rating Reviewer;
- Service Rating Exception Approver;
- Critical Override Approver;
- Project Executive Read Only;
- Auditor Read Only.

### 3.3 LodgeX platform roles

- LodgeX Support;
- LodgeX Administrator;
- Security Administrator;
- Technical Recalculation Operator;
- Audit/Compliance Administrator.

Platform roles do not automatically have business authority to approve project exceptions or change company records.

## 4. Permission catalogue

Suggested permission keys:

```text
service_rating.view_company_summary
service_rating.view_project_summary
service_rating.view_criterion
service_rating.view_evidence_summary
service_rating.view_protected_evidence
service_rating.view_history
service_rating.view_policy
service_rating.manage_policy_draft
service_rating.approve_policy
service_rating.publish_rating
service_rating.request_review
service_rating.respond_to_review
service_rating.assign_review
service_rating.decide_review
service_rating.request_exception
service_rating.approve_exception
service_rating.apply_critical_override
service_rating.resolve_critical_override
service_rating.create_corrective_action
service_rating.manage_company_action
service_rating.verify_corrective_action
service_rating.export_company_report
service_rating.export_project_report
service_rating.enter_manual_evidence
service_rating.verify_manual_evidence
service_rating.run_recalculation
service_rating.view_audit
```

## 5. Crew Hub permission matrix

| Capability | Company Owner/Admin | Workforce Manager | Scheduler | HSE/Compliance | Journey Manager | LMS Admin | Supervisor | Worker | Executive Read Only |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| View company overall grade | Yes | Yes | Yes | Yes | Yes | Yes | Scoped | No by default | Yes |
| View project criterion grade | Yes | Yes | Yes | Yes | Relevant | Relevant | Team scope | Own impact only | Yes |
| View workforce evidence | Yes | Yes | Yes | Limited | No | No | Team | No | Summary |
| View arrival evidence | Yes | Yes | Yes | Yes | Limited | No | Team | Own | Summary |
| View Journey evidence | Permission-based | Summary | No | Yes | Yes | No | Team | Own | Summary |
| View LMS evidence | Permission-based | Summary | No | Yes | No | Yes | Team | Own | Summary |
| Correct schedule/assignment | Permission-based | Yes | Yes | No | No | No | No | No | No |
| Correct Journey source | Permission-based | Limited | No | Yes | Yes | No | Team scope | Own permitted fields | No |
| Submit/verify LMS evidence | Permission-based | Limited | No | Yes | No | Yes | Team submission | Own submission | No |
| Request review | Yes | Yes | Draft if configured | Yes | Relevant draft | Relevant draft | No by default | No | No |
| Respond to review | Yes | Yes | Assigned | Yes | Assigned | Assigned | Assigned action only | No | No |
| Create company corrective action | Yes | Yes | Assigned | Yes | Assigned | Assigned | Team action | Personal action | No |
| Close company work | Yes | Yes | Limited | Yes | Limited | Limited | Assigned | Assigned personal task | No |
| Export company scorecard | Yes | Yes | Limited | Yes | No | No | No | No | Yes |

## 6. Major Projects permission matrix

| Capability | Project Admin | Project Manager | Workforce Planner | HSE Reviewer | Contract Manager | Rating Reviewer | Exception Approver | Critical Override Approver | Executive | Auditor |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| View project distribution | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Read only |
| View company criterion | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Read only |
| View protected evidence | Permission-based | Limited | Workforce only | Compliance/Journey/LMS | Contract scope | Assigned review | Decision scope | Critical scope | Aggregated | Audited read only |
| Draft policy | Yes | Limited | Workforce fields | HSE fields | Contract fields | No | No | No | No | No |
| Approve policy | Restricted | If delegated | No | Co-approval | Co-approval | No | No | No | If delegated | No |
| Enter manual evidence | Restricted | Limited | Workforce/arrival | HSE/Journey/LMS | Contract facts | Assigned | No | Critical evidence | No | No |
| Verify manual evidence | Restricted | Limited | Separate from entry | Separate from entry | Separate from entry | Assigned | No | Critical scope | No | No |
| Assign review | Yes | Yes | No | No | No | No | No | No | No | No |
| Decide review | Restricted | If assigned | Workforce scope | Compliance scope | Contract scope | Yes | Exception outcome only | Critical outcome only | Escalation | No |
| Approve exception | Restricted | If delegated | Limited | HSE scope | Contract scope | Recommend | Yes | No | Escalated | No |
| Apply critical override | No by default | Restricted | No | Recommend/approve if designated | No | No | No | Yes | Co-approval if configured | No |
| Verify corrective action | Yes | Yes | Assigned | Assigned | Assigned | Assigned | No | Critical actions | No | Read only |
| Export project report | Yes | Yes | Scoped | Scoped | Scoped | Scoped | Scoped | Scoped | Yes | Yes |
| View audit | Restricted | Limited | Limited | Relevant | Relevant | Review trail | Decision trail | Critical trail | Summary | Yes |

## 7. Segregation of duties

Recommended controls:

- a user cannot approve their own exception request;
- a user who enters manual evidence cannot be the sole verifier when dual verification is required;
- a company user cannot decide a project-issued review request;
- a policy drafter should not be the only policy approver;
- critical override application and resolution require restricted authority and optional dual approval;
- technical administrators can rerun approved calculations but cannot invent business evidence or policy decisions;
- auditors are read only.

## 8. Field-level evidence visibility

### 8.1 Workforce evidence

Project viewers may see:

- worker reference or approved display name;
- trade/position;
- project assignment;
- required dates;
- delivery status;
- qualification/readiness status.

They should not automatically see:

- compensation;
- unrelated employment details;
- internal performance notes;
- unrelated project schedule.

### 8.2 Journey evidence

Project viewers may see:

- journey required/not required;
- status;
- risk level;
- approval status;
- missed check-in count;
- expected/actual arrival;
- critical noncompliance indicator.

Detailed route, personal stops, phone number and other personal travel information require explicit purpose and permission.

### 8.3 LMS evidence

Project viewers may see:

- requirement name;
- status;
- completion/expiry;
- verifier;
- critical indicator;
- evidence reference.

Unrelated training history is not exposed.

## 9. Query-scoping rules

Every query must include authorized scope. Examples:

```php
ServiceRatingSnapshot::query()
    ->forTenant($user->tenant_id)
    ->forAuthorizedCompanies($user)
    ->forAuthorizedProjects($user)
    ->visibleTo($user)
    ->latestPublished();
```

Never rely only on UI hiding. Laravel policies, repository scopes and database query constraints must enforce access.

## 10. Review assignment visibility

A reviewer can access:

- the review request assigned to them;
- required criterion evidence;
- project policy;
- prior related decisions;
- relevant corrective actions.

Assignment does not grant unrestricted access to all company records or all project reviews.

## 11. Cross-company isolation

- Company A cannot see Company B’s rating evidence or corrective actions.
- A project executive may see aggregated distribution without protected evidence.
- A Prime relationship does not automatically expose private internal company records.
- Comparison/benchmark reports use minimum necessary and may require anonymization.
- exports enforce the same row and field scopes as the UI.

## 12. Platform support access

LodgeX Support access should be:

- time-bound;
- approved or ticket-linked;
- least privilege;
- logged;
- masked where possible;
- prohibited from changing business decisions;
- subject to break-glass controls for emergency access.

## 13. AI and MCP permission inheritance

AI and MCP tools inherit the initiating user’s scope. They cannot:

- broaden tenant/project/company access;
- retrieve hidden evidence through a different tool;
- execute business decisions without required approval;
- bypass field-level redaction;
- expose data in prompts/logs outside approved controls.

Every tool call records user/service identity, scope, purpose, inputs, outputs, approval and trace ID.

## 14. Export controls

Exports require:

- explicit permission;
- project/company scope;
- selected fields;
- classification label;
- generation timestamp;
- policy/evaluation context;
- watermark where appropriate;
- audit record;
- expiry or secure retrieval controls for generated files.

## 15. Authorization test requirements

Test at minimum:

- company user sees own project rating only;
- company user cannot approve exception;
- reviewer sees assigned review only;
- executive sees aggregate but not protected evidence;
- HSE reviewer sees Journey/LMS details but not payroll;
- scheduler cannot see unrelated sensitive documents;
- project user cannot access unrelated project;
- cross-company object ID guessing returns denied/not found;
- export respects row/field scope;
- AI/MCP call cannot exceed user permissions;
- revoked role loses access immediately or within defined session controls;
- effective-dated authority ends as scheduled;
- audit records every privileged access.
