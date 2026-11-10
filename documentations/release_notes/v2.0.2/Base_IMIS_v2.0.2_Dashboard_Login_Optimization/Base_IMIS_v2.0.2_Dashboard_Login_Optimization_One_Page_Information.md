# Base IMIS v2.0.2 - Dashboard and Login Optimization

## One-Page Release Information

| Item | Value |
|---|---|
| Branch | `v2.0.2-dashboard-login-optimization` |
| Tag after approval | `v2.0.2` |
| Status | Local implementation committed; review and production QA pending |
| Jira | `BASEIMIS-20` |

## What changes

- Login shows **Signing in...** and blocks duplicate valid submissions.
- Only the selected dashboard sidebar link shows a loader and becomes temporarily inactive.
- Main, Building, and Utility dashboards show a page shell and loader before expensive dashboard content is ready.
- Main Dashboard results use a short authorization-scoped cache.
- Building Dashboard no longer calculates unrelated hidden sections.
- Chart initialization works after asynchronous content insertion.

## Evidence

- Historical observation: login approximately 1.48 seconds; dashboard approximately 19.31 seconds.
- Controlled Main Dashboard warm median: 8,625 ms before and 3,142 ms after.
- Controlled Building Dashboard render: 931 ms / 66 queries before and 185 ms / 25 queries in the initial optimized run.
- Focused automated dashboard verification: 18 tests passed.

## Deployment impact

- No migration or seeder.
- No new Composer or NPM dependency.
- Redis/shared cache recommended for multiple application servers.
- Optional TTL values: 180 seconds fresh and 1,800 seconds stale retention.
- Deploy only after review, QA, approval, merge to stable `main`, and creation of tag `v2.0.2`.

## Release boundary

This release does not include VAPT rate limiting, clickjacking, password-reset enumeration, or unrelated CWIS/FSM work.
