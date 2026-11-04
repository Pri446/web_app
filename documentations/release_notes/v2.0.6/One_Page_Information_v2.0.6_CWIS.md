# **IMIS v2.0.6 CWIS Upgrade**

###### **ONE-PAGE RELEASE INFORMATION**

| **Version** | v2.0.6 candidate | **Branch** | v2.0.6cwis-new-indicators |
|---|---|---|---|
| **Status** | Implemented; release/QA approval pending | **Release tag** | Not assigned |
| **Code candidate** | 917cc22a; final tag pending | **Updated** | 4 November 2026 |

###### **What This Release Does**

Extends IMIS CWIS to Equity, Safety, Sustainability, Responsibility, Accountability and Resource Planning and Management. Annual source forms and operational IMIS records feed approved database calculations. Saved results support country-aware monitoring, dashboards and exports.

| **Key Changes** | **How It Works** |
|---|---|
| Six outcome groups with 142 registered indicators; SF-0a/SF-0b/SF-0c counts added | Select country and outcome, then the applicable year |
| Draft, submission, review and approval with retained evidence | Enter annual values/answers; save draft or submit; review and approve |
| Annual Treatment Plant effluent/biosolids reuse inputs | Operational and approved manual sources feed individual functions |
| Individual Accountability and RPM calculators/updaters | Official values are saved in cwis.data_cwis and generation context |
| Country-aware dashboard, Excel/PDF and explicit rebuild | Existing years are protected; authorized rebuild refreshes saved values |
| Clearer generation listing without displayed Submission ID; PDF/DOC/DOCX uploads up to 5 MB | Internal source IDs remain intact; shared backend/file-picker policy applies to new uploads |

###### **Deployment Requirements**

| **Item** | **Required Action** |
|---|---|
| Release | Lock the reviewed commit/tag; revalidate the upstream merge |
| Backup | Coordinated full database, evidence/storage and previous code backup |
| Migration | 24 CWIS migrations; existing deployments with the original 22 applied need the two new Safety-count/helper migrations |
| Seeders | Namespaced Equity catalogue/form seeders plus SS, RS, AC and RPM catalogue seeders |
| Permissions | Verify List/Add/Edit CWIS and Approve CWIS; explicit approval permission registration is not supplied by the reviewed seeders |
| Configuration | Approved country mappings, Safety settings, protected writable evidence storage and PDF renderer |

| **Minimum Verification** | **Dependencies / Important Notes** |
|---|---|
| Latest focused checks: 27 PHP tests / 265 assertions and 10 JS tests passed | Existing PostgreSQL/PostGIS and operational IMIS helpers are prerequisites |
| Full database regression and release acceptance remain incomplete | 142 registered entries leave 17 specifications unresolved against the latest 159 target |
| Verify cross-year/country isolation, role access, retained evidence and source approval | Example country assignments require approval before live use |
| Compare monitoring, dashboard, Excel/PDF and authorized rebuild outputs | Installation does not regenerate results; rollback requires a tested restore/forward-fix plan |

###### **Release References and Approval**

| **Field** | **Reference / Status** |
|---|---|
| Detailed release note | Release_Notes_v2.0.6_CWIS.md |
| Deployment instructions | documentations/CWIS_Live_Deployment_Guide.md |
| PR / release tag / production date | Pending confirmation |
| Technical Lead / QA / PM | Sign-off pending |

Implementation is complete for the added code workflows; publication and production acceptance are separate approvals.

