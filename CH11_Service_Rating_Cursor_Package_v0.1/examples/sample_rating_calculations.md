# CH-11 Sample Rating Calculations

## Example 1 — A rating

**Scope:** Company Alpha / Project North / rolling 30 days

| Criterion | Result | Grade |
|---|---|---|
| Workforce Delivery | 2% variance; all critical positions covered | A |
| Scheduled Arrival | All workers arrived on scheduled day | A |
| Journey Management | 20 of 20 required journeys compliant | A |
| LMS/Certification | 100 of 100 workers fully compliant | A |
| **Overall** | Worst applicable criterion | **A** |

## Example 2 — B because workers arrived one day late

| Criterion | Result | Grade |
|---|---|---|
| Workforce Delivery | 3% variance | A |
| Scheduled Arrival | Six workers arrived one calendar day late | B |
| Journey Management | 100% compliant | A |
| LMS/Certification | 100% compliant | A |
| **Overall** | Worst applicable criterion | **B** |

B is displayed in yellow.

## Example 3 — C due LMS gap

- applicable workers: 100;
- affected workers: 25;
- compliance gap: `25 / 100 × 100 = 25%`;
- no critical certification missing.

LMS grade: C (>20% and <=40%). Overall is C even if other criteria are A.

## Example 4 — D due critical Journey Management event

- required journeys: 100;
- noncompliant journeys: 2;
- normal rate would be 2%, which is B;
- one journey was an unauthorized high-risk journey.

Critical rule forces Journey grade D and overall D.

## Example 5 — N/A Journey Management

The project policy does not require Journey Management for project-provided bus transportation during the selected period.

| Criterion | Grade |
|---|---|
| Workforce Delivery | A |
| Arrival | A |
| Journey | N/A |
| LMS | A |
| **Overall** | **A** |

N/A is excluded; it is not treated as A or missing data.

## Example 6 — Approved arrival exception

A road was closed by an emergency order. The project approved an exception for 20 affected workers from August 12 through August 13.

Those arrival events are excluded according to the scoped exception. Remaining arrival events are on time, so Arrival is A.

## Example 7 — Source correction and new snapshot

1. Snapshot S1 publishes B because a worker appears one day late.
2. Company submits evidence that the schedule was changed before travel.
3. Project confirms approved schedule change.
4. Owning schedule record is corrected.
5. Engine recalculates and creates S2 with A.
6. S1 remains immutable and is linked as Superseded by S2.

## Example 8 — All Projects overall

Active project ratings:

| Project | Grade |
|---|---|
| Highland | A |
| North River | B |
| Coastal | B |
| Summit | C |
| Arctic | D |

Correct Crew Hub All Projects Overall Grade: **D**.

Distribution:

- A: 1;
- B: 2;
- C: 1;
- D: 1.

The distribution donut is descriptive; it does not average to B.

## Example 9 — Data stale

The LMS integration has not synchronized for 18 hours and exceeds the project freshness threshold.

- retain last valid published grade;
- mark LMS data stale;
- show Pending Data/Data Stale;
- do not automatically lower the score because records are unavailable;
- recalculate after synchronization or verified manual evidence.

## Example 10 — Workforce position protection

Project requires:

- 10 electricians;
- 10 pipefitters;
- 1 critical medic.

Company provides:

- 12 electricians;
- 9 pipefitters;
- 0 medic.

Total headcount is 21 of 21, but the critical medic is missing. Excess electricians do not offset the critical position. The critical-position rule determines the Workforce criterion according to shortage duration and policy, potentially D if unavailable for more than 3 days or material project risk exists.
