# Crew Hub UI Reference — Calculation Correction Note

The included image is the accepted visual reference for Crew Hub Company Command. Preserve:

- LodgeX navy sidebar;
- top Overall Company Rating widget;
- B shown in yellow;
- Major Projects, Total Workers, Ready Workforce, Journeys Due with bus icon, Accommodation Status, Timesheets & Approvals and Projects at Risk widgets;
- Company Performance Scorecard;
- Workforce Outlook (14 Days);
- Scorecard Summary;
- Company Performance by Project;
- Top Priority Actions;
- Upcoming Mobilizations.

## Sample-data correction

The table shows:

```text
Highland: A
North River: B
Coastal: B
Summit: C
Arctic: D
```

The top Overall Company Rating is shown as B. Under the current All Projects rule, the correct overall grade is D because the lowest active project grade wins.

Implementation rule:

```ts
const overallGrade = worstGrade(activeProjectRatings);
```

Do not hard-code the sample B. B remains yellow whenever a B rating is correctly calculated or a B-rated project is selected.
