# LodgeX Crew Hub — Functional Status Report

| Field | Value |
| --- | --- |
| Document title | Crew Hub Functional Status Report |
| Product | LodgeX Crew Hub |
| Codebase | `D:\laragon\www\crew` |
| Audit date | 21 August 2026 |
| Stack | Laravel (Inertia) + React |
| Classification | Internal implementation status |
| Audience | Product, delivery, and engineering |

---

## 1. Purpose

This document records which Crew Hub sections are **operational**, which are **partially implemented**, and which are **placeholders**. It is based on routes, controllers, Inertia pages, and feature tests in the current repository.

It is a **status report**, not a user manual. It describes what the application actually does today, not what the UI implies.

---

## 2. Status definitions

| Status | Meaning |
| --- | --- |
| **Fully functional** | Real backend, real data, and a usable end-to-end workflow. |
| **Partially functional** | Real UI with material gaps: read-only, missing CRUD, dead calls-to-action, or unpaid/gated behaviour. |
| **Placeholder / stub** | EmptyState page or hard-coded demo shell. No live product workflow. |
| **Broken** | Route crashes or cannot render. **None found.** |

---

## 3. Executive summary

Crew Hub’s operational core is usable:

- Company Command (Dashboard)
- Workers (CRUD)
- Hierarchy
- Major Projects (list, join, gated create)
- Readiness
- Timesheets end to end (entry, approval, detail, reports)
- Journey Management sub-pages (list, vehicles, questions, risk, hubs, insurance)
- Notifications, Profile, and Auth

The sidebar still exposes unfinished product areas:

- LMS
- Communications
- Equipment
- Documents

Schedule and Accommodation are useful views but remain largely **read-only**.

**Bottom line:** Crew Hub can be used for people ops, projects, hierarchy, readiness, the full timesheet cycle, and journey operations screens. It is not complete for messaging, LMS, equipment/document libraries, schedule editing, or accommodation bookings. No sidebar route hard-crashes. The main risk is false confidence from polished stubs.

---

## 4. At-a-glance matrix

| # | Section | Status | Primary routes | Tests |
| --- | --- | --- | --- | --- |
| 1 | Company Command (Dashboard) | Fully functional | `GET /dashboard` | `CrewHubDashboardTest`, `ServiceRatingCalculatorTest` |
| 2 | Workers | Partially functional | `/workers` CRUD + tools / activity / certificates | `WorkerManagementTest`, `WorkerTrainingTabTest` |
| 3 | Hierarchy | Fully functional | `/hierarchy`, managers, delegations, workforce | `HierarchyTest` |
| 4a | Major Projects — Current Projects | Fully functional | `/major-projects` resource + switch | `MajorProjectsModuleAccessTest` |
| 4b | Major Projects — Join a Project | Fully functional | `/major-projects-join`, invitation accept/decline | `MajorProjectsModuleAccessTest` |
| 4c | Major Projects — Create a Project | Partially functional | create + `modules.request-activation` | `MajorProjectsModuleAccessTest` |
| 5 | Schedule | Partially functional | `GET /schedule` only | `ScheduleBoardTest` |
| 6a | Timesheets — Overview & Detail | Fully functional | index/show + submit/approve/return/reject/run-check | `TimesheetDetailSmokeTest`, `TimesheetApprovalWorkflowTest`, `CampTimesheetSyncTest` |
| 6b | Timesheets — Entry | Fully functional | `GET /timesheets/entry`, `POST /timesheets` | `TimesheetEntryTest` |
| 6c | Timesheets — Approval tab | Fully functional | `GET /timesheets/approval` | `TimesheetApprovalWorkflowTest` |
| 6d | Timesheets — Reports | Fully functional | `GET /timesheets/reports` + `/reports/export` | `TimesheetReportsTest` |
| 7 | Readiness | Fully functional | `/readiness`, `POST /readiness/run-check` | `CrewHubDashboardTest` (index smoke) |
| 8a | Journey — All Journey List | Fully functional | index/store/show/status/export | `JourneyManagementTest` |
| 8b | Journey — Registered Vehicles | Fully functional | vehicles index/create/store | `VehicleRegistrationTest` |
| 8c | Journey — Journey Questions | Fully functional | questions CRUD + reorder | `JourneyQuestionTest` |
| 8d | Journey — Calculate Risk | Fully functional | risk index/store/recalculate/export | `JourneyRiskAssessmentTest`, `RiskEngineTest` |
| 8e | Journey — Journey Hubs | Fully functional | hubs CRUD + designate | `JourneyHubTest` |
| 8f | Journey — Vehicle Insurance | Fully functional | insurance index + confirm | `VehicleInsuranceTest` |
| 9 | Accommodation | Partially functional | `GET /accommodations`, `GET /accommodations/{id}` | `AccommodationDashboardTest` |
| 10 | LMS | Placeholder / stub | `GET /lms` (closure) | None |
| 11 | Communications | Placeholder / stub | `GET /communications` (closure) | None |
| 12 | Equipment | Placeholder / stub | `GET /equipment` (closure) | None |
| 13 | Documents | Placeholder / stub | `GET /documents` (closure) | None |
| 14 | Settings (+ Modules admin) | Partially functional | `/settings`, `/settings/modules/*` | `MajorProjectsModuleAccessTest` |
| 15 | Notifications (header) | Fully functional | `/notifications` + mark read + activation actions | `MajorProjectsModuleAccessTest` (activation path) |
| 16 | Profile / Auth | Fully functional | `routes/auth.php`, `/profile` | `Feature/Auth/*`, `ProfileTest` |

**Counts:** 16 top-level areas audited across 26 rows · 17 fully functional · 5 partially functional · 4 placeholders.

---

## 5. Section-by-section detail

### 5.1 Company Command (Dashboard)

| Item | Detail |
| --- | --- |
| Status | Fully functional |
| Route | `GET /dashboard` (`dashboard`) |
| Controller | `app/Http/Controllers/DashboardController.php` |
| Page | `resources/js/Pages/Dashboard/Index.jsx` |

**What works**

- Live KPI strip (readiness, accommodations, journeys, timesheets, service rating).
- Project-scoped data via current project session.
- Forecast and scorecards from `DashboardService` and database models.
- Auto-refresh (approximately every 15 minutes).

**Gaps**

- No per-user dashboard layout customizer.
- Sidebar link “Customize Dashboard” opens Settings, not a dashboard editor.

**Tests:** `tests/Feature/CrewHubDashboardTest.php`, `tests/Feature/ServiceRating/ServiceRatingCalculatorTest.php`

---

### 5.2 Workers

| Item | Detail |
| --- | --- |
| Status | Partially functional |
| Routes | `/workers` resource; `PATCH /workers/{worker}/tools`; `GET /workers/{worker}/activity`; certificate store/destroy |
| Pages | `Workers/Index`, `Create`, `Show`, `Edit`, `Activity` |

**What works**

- List with search, filter, and pagination.
- Create worker, including document upload on create.
- Edit, archive, and show profile.
- Training tab and readiness panel on worker show.
- Certificate upload/destroy and activity log.

**Gaps**

- Company-wide worker feature toggles are live and respect Major Project module settings.
- Per-worker tool-access UI exists in code but is not wired into the live page.
- Central LMS module is a stub; training lives on the worker profile only.

**Tests:** `WorkerManagementTest`, `WorkerTrainingTabTest`

---

### 5.3 Hierarchy

| Item | Detail |
| --- | --- |
| Status | Fully functional |
| Routes | `GET /hierarchy`; managers store/destroy; `PATCH` delegations; `GET /hierarchy/workforce` |
| Page | `resources/js/Pages/Hierarchy/Index.jsx` |

**What works**

- Connect and disconnect managers.
- Delegation toggles.
- Workforce modal (including accommodation/reservation columns).
- Project tabs and approval path.

**Gaps**

- “Edit Connection” navigates to major-project edit, not a hierarchy-specific editor.

**Tests:** `HierarchyTest`

---

### 5.4 Major Projects

#### 5.4.1 Current Projects — Fully functional

- Paginated list, filters, details panel.
- Role-gated actions, project switcher, archive/edit/show.

**Gap:** Show page is a summary. There is no deep workers/modules management on the show page.

#### 5.4.2 Join a Project — Fully functional

- Invitation inbox with filters and details panel.
- Accept and decline invitation actions.

#### 5.4.3 Create a Project — Partially functional

- Full create form when the paid module is activated.
- Locked state plus activation request when not activated.
- Super-admin invite flow for companies.

**Gaps**

- Paid module gating and activation are required before create.
- Online payments are not available.
- Module catalog lists many modules, but only Major Projects is enforced as paid.

**Tests:** `MajorProjectsModuleAccessTest`

---

### 5.5 Schedule

| Item | Detail |
| --- | --- |
| Status | Partially functional |
| Route | `GET /schedule` only |
| Page | `resources/js/Pages/Schedule/Index.jsx` |

**What works**

- Read-only schedule board from `ScheduleBoardService`.
- Project tabs, worker rows, accommodation status column.
- Footer totals (in lodge / project personnel).

**Gaps**

- Schedule editing is explicitly disabled (“schedule editing is not enabled yet”).
- Publish and Reset controls are inert.
- No write/update routes for schedule days.

**Tests:** `ScheduleBoardTest`

---

### 5.6 Timesheets

#### 5.6.0 Approval model — one human gate

The workflow is **worker submits → manager approves → fully approved (locked)**. There is no client approval gate: `timesheets.client_approval_enabled` is `false`, which collapses manager approval straight to `fully_approved` and hides the client stage from queues, filters, KPIs, records, and exports. The `client_*` columns and the `approve-client` route remain in place, unused, so the second gate can be restored per deployment.

Approval authority comes from the Hierarchy Chart: when a project has an accepted **Time Sheets** responsibility delegation, only that manager may approve, return, or reject. Projects with no accepted delegation fall back to the `CompanyAdmin` / `WorkforceManager` roles so approvals are never blocked by an unconfigured hierarchy.

#### 5.6.1 Overview and Detail — Fully functional

**Routes:** index/show; PATCH update; POST submit, approve-manager, return, reject, run-check.

**What works**

- Approval queue with filters.
- Manager approve, return, reject.
- Detail edit and submit.
- Camp sync “Run Check”.
- Client-side CSV export from the queue.

**Tests:** `TimesheetDetailSmokeTest`, `TimesheetApprovalWorkflowTest`, `CampTimesheetSyncTest`

#### 5.6.2 Entry tab — Fully functional

- Routes: `GET /timesheets/entry` → `TimesheetController@entry`; `POST /timesheets` creates the draft.
- Roster of timesheet-eligible workers for the selected week, backed by `TimesheetEntryService`.
- Per-week status per worker (not started, draft, returned, in approval), with counts that double as filters.
- “Start timesheet” creates a blank seven-day draft and opens the detail page for hour capture; repeat clicks reuse the existing sheet.
- Project switcher, debounced worker search, status filter, pagination, and Camp sync.
- **Tests:** `TimesheetEntryTest`

#### 5.6.3 Approval tab — Fully functional

- Route: `GET /timesheets/approval` → `TimesheetController@approval`.
- Renders the same live queue as the overview via the shared `ApprovalWorkspace` component, so approve / return / request-changes behave identically on both tabs.
- Now present in both the sidebar children and the header `TIMESHEET_TABS`.
- **Tests:** `TimesheetApprovalWorkflowTest`

#### 5.6.4 Reports — Fully functional

- Routes: `GET /timesheets/reports` → `TimesheetReportController`; `GET /timesheets/reports/export` streams CSV.
- All KPIs, charts, exceptions, and the report library are computed from timesheet data by `TimesheetReportService`.
- Filters (project, week, report type, status, search) drive the payload; Generate re-queries and Export downloads.
- Report library, generated-reports table, and quick exports all stream real CSVs: summary, approval aging, missing timesheets, hours by worker/position, equipment hours, AI accommodations.
- **Gap:** recurring emailed report schedules are not implemented; the panel states this instead of showing demo rows.
- **Tests:** `TimesheetReportsTest`

---

### 5.7 Readiness

| Item | Detail |
| --- | --- |
| Status | Fully functional |
| Routes | `GET /readiness`; `POST /readiness/run-check` |
| Page | `resources/js/Pages/Readiness/Index.jsx` |

**What works**

- Stats, charts, attention list, expiry widgets.
- Run Readiness Check recalculates worker readiness.

**Gaps**

- Quick Action “Report Issue” opens Readiness, not a dedicated issue form.

**Tests:** `CrewHubDashboardTest` (index smoke)

---

### 5.8 Journey Management

All six Journey UI modules are implemented and tested. The original 10-stage **automation** pipeline is not: Camp travel-day trigger, duplicate reuse, invitations/participants wizard, live checkpoint monitoring, and stakeholder notification fan-out.

#### 5.8.1 All Journey List — Fully functional

- List, filter, stats, create modal, details panel.
- Status updates and CSV export.
- Risk gated: new journeys start pending; low risk can auto-approve.
- **Gap:** No journey edit/delete routes (create, show, status only).
- **Tests:** `JourneyManagementTest`

#### 5.8.2 Registered Vehicles — Fully functional

- Vehicle list, register form, draft save, insurance document upload.
- **Gap:** No vehicle edit/delete routes.
- **Tests:** `VehicleRegistrationTest`

#### 5.8.3 Journey Questions — Fully functional

- Create, edit, delete, library templates, drag reorder.
- **Tests:** `JourneyQuestionTest`

#### 5.8.4 Calculate Risk — Fully functional

- Assessment list, factor breakdown, recalculate, CSV export.
- Low-risk auto-approve path.
- **Gap:** Screen can look empty without journeys/answers (operational data is not seeded).
- **Tests:** `JourneyRiskAssessmentTest`, `tests/Unit/RiskEngineTest.php`

#### 5.8.5 Designation to Journey Hub — Fully functional

- Hub registry CRUD and designate undesignated journeys.
- **Tests:** `JourneyHubTest`

#### 5.8.6 Confirmation of Vehicle Insurance — Fully functional

- Confirmation queue, confirm/flag, cover filters, verifier audit fields.
- **Gap:** Insurance status is recorded but not yet enforced as a hard block on journey approval.
- **Tests:** `VehicleInsuranceTest`

---

### 5.9 Accommodation

| Item | Detail |
| --- | --- |
| Status | Partially functional |
| Routes | `GET /accommodations`; `GET /accommodations/{accommodation}` only |
| Pages | `Accommodations/Index.jsx`, `Accommodations/Show.jsx` |

**What works**

- Two-panel hub: Major Project Accommodations and Accommodations Concierge.
- Linked project card, room/arrival metrics, facility list.
- Facility show with assignments.

**Gaps**

- Read-only. No create/update/booking assignment routes.
- No dedicated reservation calendar or accommodation reports routes.
- Concierge and Help calls-to-action link to the Communications stub.

**Tests:** `AccommodationDashboardTest`

---

### 5.10 LMS — Placeholder / stub

- Route: `GET /lms` (Inertia closure).
- EmptyState: “Learning management coming soon”.
- Training and certificates already exist on the worker profile, not here.
- Tests: none.

---

### 5.11 Communications — Placeholder / stub

- Route: `GET /communications` (Inertia closure).
- EmptyState: “Team messaging and broadcasts will live here.”
- Dead end for Quick Action “New Message” and Accommodation concierge/help links.
- Tests: none.

---

### 5.12 Equipment — Placeholder / stub

- Route: `GET /equipment` (Inertia closure).
- EmptyState: “Equipment register coming soon”.
- Timesheet detail may show equipment usage rows, but there is no register module.
- Tests: none.

---

### 5.13 Documents — Placeholder / stub

- Route: `GET /documents` (Inertia closure).
- EmptyState: “Document library coming soon”.
- Worker document upload exists on create/edit worker only.
- Tests: none.

---

### 5.14 Settings and Modules admin

| Item | Detail |
| --- | --- |
| Status | Partially functional |
| Routes | `GET /settings`; `/settings/modules` grant/revoke/paid; `POST /modules/{module}/request-activation` |

**What works**

- Settings hub with profile link.
- Super-admin Modules admin: grant/revoke, paid toggle, pending activation requests.

**Gaps**

- No company-level settings beyond profile.
- Module catalog lists all sidebar modules, but only Major Projects is paid/enforced in application behaviour.

**Tests:** `MajorProjectsModuleAccessTest`

---

### 5.15 Notifications (header bell)

| Item | Detail |
| --- | --- |
| Status | Fully functional |
| Routes | `GET /notifications`; mark read; mark all read; activation approve/reject |

**What works**

- Inbox, mark read, mark all read.
- Module activation approve/reject from notifications.

**Gaps**

- Not in the sidebar.
- Broader journey/stakeholder notification fan-out is not wired.

**Tests:** `MajorProjectsModuleAccessTest` (activation path)

---

### 5.16 Profile and Auth

| Item | Detail |
| --- | --- |
| Status | Fully functional |
| Routes | `routes/auth.php`; `/profile` edit/update/destroy |

**What works**

- Login, register, password reset/confirm, email verification, logout.
- Profile edit, password, delete account.

**Gaps**

- Main application requires verified email; profile is under `auth`-only middleware.

**Tests:** `tests/Feature/Auth/*`, `ProfileTest`

---

## 6. Quick Actions

Defined in `resources/js/utils/constants.js` (`QUICK_ACTIONS`).

| Action | Target route | Status | Reality |
| --- | --- | --- | --- |
| Add Worker | `workers.create` | Fully functional | Opens worker create flow. |
| Create Journey | `journeys.index` | Partially functional | Opens journey list; user must click New Journey. |
| Report Issue | `readiness.index` | Partially functional | Opens Readiness dashboard, not an issue form. |
| New Message | `communications.index` | Placeholder / stub | Communications EmptyState only. |

---

## 7. Route stubs and demo shells

### 7.1 Inertia-only stubs

Defined as closures in `routes/web.php` with no controller data:

| Route name | URI | Page |
| --- | --- | --- |
| `communications.index` | `/communications` | `Communications/Index` |
| `lms.index` | `/lms` | `Lms/Index` |
| `equipment.index` | `/equipment` | `Equipment/Index` |
| `documents.index` | `/documents` | `Documents/Index` |

`GET /settings` is also a closure, but it is a real hub page (profile + modules link), not an EmptyState.

### 7.2 Looks finished but is not live

| Surface | Reality |
| --- | --- |
| Scheduled report emails | Timesheet Reports computes live data, but recurring email schedules are not built. |
| Schedule modifications | Board shows live data; edit/publish controls are intentionally disabled. |
| Accommodation Concierge | Marketing panel is real UI; request/help links go to Communications stub. |

---

## 8. Test coverage notes

Covered by feature tests: Dashboard, Workers, Hierarchy, Major Projects / Modules, Schedule board, Timesheets (entry, approval workflow, detail, reports, Camp sync), Journey sub-areas, Accommodation hub, Auth/Profile.

**No dedicated tests for:** LMS, Communications, Equipment, Documents, Settings hub page, Notifications beyond module activation.

---

## 9. Recommended build priority

| Priority | Area | Why it matters |
| --- | --- | --- |
| 1 | Communications | Multiple CTAs and Quick Actions already point here; currently a dead end. |
| 2 | Schedule write / publish | Board exists; editing and publish are inert. |
| 3 | Accommodation booking CRUD and calendar | Hub UI exists; reservations/calendar/reports still missing. |
| 4 | Hide or build LMS / Documents / Equipment | Sidebar promises modules that are EmptyStates. |
| 5 | Journey automation (stages 1–3) | Manual journey CRUD works; Camp travel-day trigger and dedupe are not built. |
| 6 | Scheduled report emails | Reports are live and exportable, but recurring delivery is not built. |

---

## 10. Source files used for this audit

| Layer | Path |
| --- | --- |
| Routes | `routes/web.php`, `routes/auth.php` |
| Navigation | `resources/js/utils/constants.js` |
| Sidebar | `resources/js/Components/Layout/Sidebar.jsx` |
| Controllers | `app/Http/Controllers/*` |
| Pages | `resources/js/Pages/**` |
| Tests | `tests/Feature/**`, `tests/Unit/RiskEngineTest.php` |

---

## 11. Document control

| Version | Date | Change |
| --- | --- | --- |
| 1.0 | 21 August 2026 | Initial functional status report from codebase audit. |
| 1.1 | 23 August 2026 | Timesheet Entry, Approval tab, and Reports built out; approval flow reduced to a single manager gate scoped by the Hierarchy Chart. |

**End of document.**
