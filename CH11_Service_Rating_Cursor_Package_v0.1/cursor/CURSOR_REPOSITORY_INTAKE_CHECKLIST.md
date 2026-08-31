# Cursor Repository Intake Checklist

Complete before proposing code.

## Application architecture

- [ ] Laravel/PHP versions and conventions
- [ ] React/TypeScript or JavaScript conventions
- [ ] Inertia setup and page-prop conventions
- [ ] Tailwind configuration and semantic tokens
- [ ] repository module/domain/package structure
- [ ] primary-key convention
- [ ] test frameworks and factories

## Existing domain records

- [ ] tenant/organization/company models
- [ ] project and company-project relationship
- [ ] worker/person identity
- [ ] workforce demand and commitments
- [ ] company schedule and assignments
- [ ] arrival/check-in/no-show
- [ ] Journey Management
- [ ] LMS/certification/readiness
- [ ] corrective actions/issues
- [ ] document/evidence storage

## Platform services

- [ ] RBAC/ABAC and Laravel policies
- [ ] functional/approval authorities
- [ ] workflow/state machine
- [ ] notifications and acknowledgements
- [ ] audit/event history
- [ ] queues, retries and dead-letter handling
- [ ] outbox or after-commit event pattern
- [ ] feature flags
- [ ] reporting/export
- [ ] observability

## UI

- [ ] Crew Hub Company Command route/page
- [ ] Major Projects dashboard/performance route/page
- [ ] shared card/table/filter/modal/drawer components
- [ ] existing chart library
- [ ] design tokens and accessibility patterns
- [ ] responsive conventions

## Integration

- [ ] internal API conventions
- [ ] external API/webhook conventions
- [ ] event envelope/versioning
- [ ] MCP architecture, if present
- [ ] AI service boundary, if present

## Required output

- [ ] exact reusable files/classes
- [ ] exact files to add/change
- [ ] conflicts/duplicates
- [ ] missing source records
- [ ] security/privacy concerns
- [ ] data migration/backfill needs
- [ ] open business decisions
- [ ] phased plan and rollback
