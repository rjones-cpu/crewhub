# CH-11 Navigation and Route Map

Routes are recommendations; Cursor must align them with existing route naming and tenancy/project middleware.

## Crew Hub

| UI | Suggested route | Permission |
|---|---|---|
| Company Command | `/crew-hub/company-command` | company summary view |
| Service Rating overview | `/crew-hub/service-ratings` | company rating view |
| Project scorecard | `/crew-hub/service-ratings/{project}` | company/project scope |
| Criterion detail | `/crew-hub/service-ratings/{project}/criteria/{criterion}` | criterion/evidence permission |
| Reviews list | `/crew-hub/service-ratings/reviews` | company review view |
| Create review | `/crew-hub/service-ratings/{snapshot}/reviews/create` | request review |
| Review detail | `/crew-hub/service-ratings/reviews/{review}` | participant/authorized view |
| Corrective actions | `/crew-hub/service-ratings/corrective-actions` | company action scope |
| Action detail | `/crew-hub/service-ratings/corrective-actions/{action}` | assigned/authorized |
| History | `/crew-hub/service-ratings/{project}/history` | history view |

## Major Projects

| UI | Suggested route | Permission |
|---|---|---|
| Project company performance | `/major-projects/{project}/performance/service-ratings` | project summary |
| Company scorecard | `/major-projects/{project}/companies/{company}/service-rating` | project/company scope |
| Policy list/editor | `/major-projects/{project}/governance/service-rating-policy` | policy view/manage |
| Review queue | `/major-projects/{project}/governance/service-rating-reviews` | review queue |
| Review workspace | `/major-projects/{project}/governance/service-rating-reviews/{review}` | assigned reviewer |
| Exceptions | `/major-projects/{project}/governance/service-rating-exceptions` | exception view/manage |
| Critical overrides | `/major-projects/{project}/governance/service-rating-critical-overrides` | restricted |
| Corrective actions | `/major-projects/{project}/governance/service-rating-corrective-actions` | project action scope |
| Reports | `/major-projects/{project}/reports/service-ratings` | report permission |
| Audit | `/major-projects/{project}/governance/service-rating-audit` | audit permission |

## Navigation behavior

- Company Command rating widget links to selected project detail or All Projects overview.
- Company Performance table rows link to project scorecards.
- Major Projects company table rows link to company detail.
- Evidence actions link to the owning source workflow.
- Review/action badges link to their workflow, not generic notifications.
- Browser back navigation preserves filters where feasible.
