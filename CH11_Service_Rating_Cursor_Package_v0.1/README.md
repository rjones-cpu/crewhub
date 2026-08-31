# LodgeX CH-11 Service Rating — Cursor Implementation Package

**Package version:** 0.1  
**Prepared:** August 15, 2026  
**Status:** Working Draft — implementation handoff, not an approved production baseline  
**Owner:** Digital 6 Marketing Inc. / LodgeX  
**Primary decision authority:** Ralph Jones  
**Classification:** Confidential — authorized LodgeX project use only

## 1. Purpose

This package translates the approved direction for the LodgeX **CH-11 Service Rating** into user manuals, scoring rules, cross-product workflows, detailed Crew Hub and Major Projects UI requirements, and a build sequence for the current LodgeX stack:

- Laravel application services and domain logic;
- React with Inertia;
- Tailwind CSS;
- MySQL;
- queues and domain events;
- governed MCP and AI explanation support.

The Service Rating evaluates a Contractor Company in the context of a specific Major Project using four primary criteria:

1. workforce delivery;
2. scheduled arrival performance;
3. Journey Management compliance, when applicable;
4. LMS and certification compliance, when applicable.

The final grade is **A, B, C or D**. The worst applicable criterion wins. Grades are not averaged.

## 2. Controlling product boundaries

- **Crew Hub / CH-11** owns the company-facing scorecard experience, source-data correction, evidence submission, review request, corrective-action management and rating history.
- **Crew Hub / CH-01** displays the Overall Grade Score as a major Company Command widget.
- **Major Projects / MP-08** displays company performance, trends, criterion drivers and project-wide rating distribution.
- **Major Projects / MP-09** governs policy, tolerances, effective dates, project-authorized exceptions, reviews, critical overrides, visibility and audit reporting.
- **Enterprise Core** provides identity, permissions, workflow, notifications, versioned rules, evidence references and audit infrastructure.
- **AI does not calculate or change the official grade.** It may explain the result, identify deterioration risk, rank corrective actions and draft a review package.

## 3. Important visual correction

The included Crew Hub reference image is retained as the accepted visual direction. Its sample data contains one inconsistency:

- the company-project table includes a **D** project;
- the top Overall Company Rating displays **B**.

Under the current All Projects rule, the top rating must be the lowest active project rating. Therefore, that sample would display **D**, not B, unless the user has filtered to a subset that excludes the D project. The implementation and UI specifications in this package enforce the correct rule.

## 4. Package contents

### User and operating manuals

- `docs/01_Executive_Overview_and_Governance.md`
- `docs/02_Crew_Hub_User_Manual.md`
- `docs/03_Major_Projects_User_Manual.md`
- `docs/04_Scoring_Policy_and_Calculation_Manual.md`
- `docs/05_Cross_Product_Interaction_Manual.md`
- `docs/06_Dispute_Exception_Override_and_Corrective_Action_Manual.md`
- `docs/07_Roles_Permissions_and_Data_Visibility.md`

### UI functional design

- `docs/08_Crew_Hub_UI_Functional_Design.md`
- `docs/09_Major_Projects_UI_Functional_Design.md`
- `ui/component_catalogue.md`
- `ui/navigation_and_route_map.md`
- `assets/crew_hub_company_command_reference.png`
- `assets/UI_REFERENCE_CORRECTION_NOTE.md`

### Engineering and implementation

- `docs/10_Technical_Implementation_Architecture.md`
- `docs/11_API_Events_and_MCP_Contracts.md`
- `docs/12_Test_Acceptance_and_Release_Plan.md`
- `docs/13_Implementation_Backlog.md`
- `docs/14_Open_Decisions_and_Production_Gates.md`
- `database/REFERENCE_SCHEMA.sql`
- `database/LARAVEL_MIGRATION_PLAN.md`
- `config/service_rating_policy_v1_working_default.json`
- `config/service_rating_policy_schema.json`
- `examples/sample_rating_calculations.md`
- `examples/event_payloads.json`

### Cursor instructions

- `.cursor/rules/lodgex-service-rating.mdc`
- `cursor/CURSOR_MASTER_IMPLEMENTATION_PROMPT.md`
- `cursor/CURSOR_PHASE_PROMPTS.md`
- `cursor/CURSOR_REPOSITORY_INTAKE_CHECKLIST.md`
- `cursor/CURSORIGNORE_RECOMMENDATIONS.md`

### Combined document

- `combined/LodgeX_CH11_Service_Rating_Combined_Manual_v0.1.md`

## 5. How to use this package in Cursor

1. Extract the package into a documentation or architecture folder in the LodgeX repository.
2. Copy `.cursor/rules/lodgex-service-rating.mdc` into the repository’s `.cursor/rules/` directory, merging rather than overwriting existing rules.
3. Open `cursor/CURSOR_REPOSITORY_INTAKE_CHECKLIST.md` and have Cursor inventory the existing Laravel, React/Inertia, database, authorization, event and testing conventions before it proposes code.
4. Start Cursor in Plan Mode and paste `cursor/CURSOR_MASTER_IMPLEMENTATION_PROMPT.md`.
5. Require Cursor to return a repository-specific implementation plan before editing files.
6. Implement one phase at a time using `cursor/CURSOR_PHASE_PROMPTS.md`.
7. Do not allow Cursor to create an AI-based grade calculator, direct cross-module database writes, mutable historical snapshots or unrestricted manual grade editing.
8. Run the full acceptance plan before enabling the feature for a production project.

## 6. Non-negotiable implementation rules

- One rating is calculated for one `company_id + project_id + policy_version + evaluation_window`.
- The lowest applicable criterion grade is the overall grade.
- A requires every applicable criterion to be A.
- N/A criteria are excluded.
- Approved, effective-dated exceptions are applied before calculation.
- Valid critical failures force D.
- Source corrections create a new calculation; they do not overwrite history.
- Users do not routinely choose A, B, C or D from a dropdown.
- Major Projects establishes policy and reviews disputed evidence; Crew Hub manages company records and review submissions.
- AI may explain and forecast but may not assign, waive, override, suppress or publish the official grade.
- Every material action is authorized, reason-coded and auditable.

## 7. Working assumptions requiring owner confirmation

The package recommends, but does not silently approve:

- a rolling 30-day operational evaluation window;
- event-triggered recalculation plus a daily checkpoint;
- configurable repeated-B escalation;
- configurable review response deadlines;
- a structured manual-evidence workflow for integrations that are not yet available;
- monthly locked reporting snapshots in addition to the live operational score.

These decisions are listed in `docs/14_Open_Decisions_and_Production_Gates.md`.
