# Laravel Migration Plan — CH-11 Service Rating

## 1. First rule

Cursor must inspect existing table names, primary-key types, tenant scoping, timestamps, soft deletes, audit strategy and model namespaces before generating migrations. `REFERENCE_SCHEMA.sql` is conceptual.

## 2. Recommended migration sequence

1. `create_service_rating_policies_table`
2. `create_service_rating_policy_versions_table`
3. add current version FK after both tables exist
4. `create_company_project_service_ratings_table`
5. `create_service_rating_snapshots_table`
6. add current published snapshot FK
7. `create_service_rating_criterion_results_table`
8. `create_service_rating_evidence_links_table`
9. `create_service_rating_exceptions_table`
10. `create_service_rating_critical_overrides_table`
11. `create_service_rating_review_requests_table`
12. `create_service_rating_review_messages_table`
13. `create_service_rating_corrective_actions_table`
14. `create_service_rating_manual_evidence_table`
15. add permission seeds/role mappings using existing authorization system
16. add feature flags/project module activation

## 3. Migration requirements

- follow existing FK naming and delete behavior;
- prefer restrict/no-action for immutable history;
- do not cascade-delete snapshots when company/project relationships change;
- index company/project/current status and review queues;
- unique calculation/idempotency key;
- policy active-version overlap protection in application and database where feasible;
- JSON fields only where flexible/versioned structures are appropriate;
- searchable/reporting fields remain normalized;
- attachments use existing document service references;
- audit uses existing platform mechanism rather than duplicate logs.

## 4. Immutability controls

Use one or more:

- model observers/guards rejecting update/delete;
- repository exposing create/read only;
- database permissions or triggers if consistent with platform standards;
- append-only lifecycle tables;
- tests proving normal code cannot mutate calculation fields.

## 5. Data backfill

No historical official grade should be invented without an approved policy and sufficient evidence.

Backfill options:

- start current rating from activation date;
- calculate historical shadow snapshots for validation only;
- import prior manual scorecards as reference records, clearly labeled non-engine-generated;
- create no A grade merely because no historical deficiencies are recorded.

## 6. Rollback

Schema rollback must not be used in production to erase official rating history. Production rollback should disable feature flags and consumers while retaining data. Destructive migration rollback is limited to non-production or approved data migration procedures.
