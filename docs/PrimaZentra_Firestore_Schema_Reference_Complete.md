# PrimaZentra — Firestore Database Schema Reference (Complete)

**Project:** PrimaZentra — construction project management & resource routing for Prima Solutions (engineering consultancy)

**Stack:** Web (CodeIgniter 4) · Mobile (Flutter, offline-capable) · Firebase/Firestore · FastAPI (ML — delay prediction & blocker classification) · MCDA + Google Maps API

**Last Updated:** June 2026

---

## Quick Navigation
- [Conventions](#conventions)
- [1. users](#1-users)
- [2. manpower](#2-manpower)
- [3. resource](#3-resource)
- [4. milestone_templates](#4-milestone_templates)
- [5. project](#5-project)
  - [milestones subcollection](#project--milestones-subcollection)
  - [daily_progress_reports subcollection](#project--daily_progress_reports-subcollection)
  - [emergency_reports subcollection](#project--emergency_reports-subcollection)
- [6. resource_requests](#6-resource_requests)
- [7. tool_inventory_logs](#7-tool_inventory_logs)
- [8. notifications](#8-notifications) **[NEW]**
- [9. chats](#9-chats) **[NEW]**
- [ML Prediction Payload](#ml-prediction-payload-fastapi)
- [Calculations & Write Flow](#calculations--write-flow)
- [Cross-collection Reference Map](#cross-collection-reference-map)
- [Role Permissions](#role-permissions)

---

## Conventions (apply to all collections)

- Every document has `created_at` and `updated_at` (`timestamp`)
- **Calculated / rollup fields** (e.g. `available_quantity`, `overall_progress`, `completion_percent`) are maintained on write via Firestore **transaction or batch**
- **All concurrent / multi-writer mutations use transactions** — both approval path and arrival-scan path that mutate `reserved_resources[]`
- Cumulative numbers (e.g. `milestone.completed_quantity`) use `FieldValue.increment()` — never read-then-write for offline safety
- **Status value casing:** Three patterns coexist; reads should be case-insensitive
  - Transfer-lifecycle: Title Case (`Transfer Pending`, `Deployed`, `In-Use`, `Completed`)
  - Fields from `buildManpowerData()`: UPPERCASE (`status`, `role`, `specialization`, `skills`)
  - Soft-delete: lowercase `"archived"`
- **Canonical project-category vocabulary** — stored ALL CAPS:
  - `"BUILD-TO-SUIT"`, `"RETROFITTING"`, `"COLOCATION"`, `"CONVERSION"`
  - Used identically by `project.project_type`, `milestone_templates` document IDs, and `manpower.specialization`

---

## 1. users

**Path:** `/users/{user_id}`

| Field | Type | Notes |
|---|---|---|
| `email` | string | |
| `country_code` | string | e.g. `"+63"` — normalized UPPERCASE, whitespace stripped |
| `contact_number` | string | digits only, no spaces/dashes |
| `contact_no` | string | derived display: `"${country_code} ${contact_number}"` |
| `first_name` | string | UPPERCASE |
| `middle_name` | string | UPPERCASE |
| `last_name` | string | UPPERCASE |
| `role` | enum | `"Administrator"`, `"Operations Manager"`, `"Project Manager"`, `"Site Engineer"` |
| `status` | string \| absent | `"archived"` (lowercase) when soft-deleted via Staff table; absent on active accounts |
| `must_change_password` | boolean | `true` for **all** roles on admin-created accounts; set to `false` after user successfully changes temporary password |
| `temp_password_expires_at` | timestamp \| absent | present only while `must_change_password == true`; set to `now + 15 minutes` at account creation. Login rejected if past this time. Deleted (via `FieldValue.delete()`) on successful password change. |
| `profile_photo_url` | string \| null | Firebase Storage download URL; `null` / absent if no photo uploaded. Storage path: `profile_pictures/{user_id}` (single file, overwritten on re-upload) |
| `created_at` / `updated_at` | timestamp | |

---

## 2. manpower

**Path:** `/manpower/{manpower_id}` — created via `.add()`

### Base Fields

| Field | Type | Notes |
|---|---|---|
| `employee_id` | string | canonical identifier, e.g. `"PSDCC-FLD-001"` |
| `name` | string | denormalized full name, UPPERCASE, e.g. `"JUAN DELA CRUZ"` |
| `first_name` | string | UPPERCASE |
| `middle_name` | string | UPPERCASE; empty string if not provided |
| `last_name` | string | UPPERCASE |
| `status` | enum | `"AVAILABLE"`, `"DEPLOYED"`, `"ON-LEAVE"` (UPPERCASE at create); `"Transfer Pending"` (Title Case during transfer); `"archived"` (lowercase — soft-delete) |
| `role` | enum | `"FIELD WORKER"`, `"FOREMAN"` (UPPERCASE) |
| `specialization` | enum | `"BUILD-TO-SUIT"`, `"RETROFITTING"`, `"COLOCATION"`, `"CONVERSION"` (UPPERCASE — canonical vocabulary) |
| `skills` | array<string> | e.g. `["ELECTRICIAN", "LABOR/HELPER"]` (UPPERCASE); `"FOREMAN"` if role is Foreman |
| `assigned_project_id` | string \| null | Firestore doc ID of `/project/{id}`; `null` if Available |
| `location` | map | `address` (string, UPPERCASE), `type` (`"WAREHOUSE"` \| `"PROJECT SITE"`, UPPERCASE), `geopoint` (GeoPoint \| null) |
| `created_at` / `updated_at` | timestamp | |

### Transfer Fields (stamped on by approval transaction)

| Field | Type | When |
|---|---|---|
| `transfer_state` | string | `"Transferring"` on lock; `"Completed"` on arrival |
| `transfer_locked` | boolean | `true` on lock; `false` on arrival |
| `transfer_request_id` | string \| null | `resource_requests` doc id; `null` after arrival |
| `transfer_source_site_id` | string | source project doc id |
| `transfer_source_site_name` | string | source project name |
| `transfer_destination_site_id` | string | destination project doc id |
| `transfer_destination_site_name` | string | destination project name |
| `transfer_locked_at` | timestamp | set on lock |
| `transfer_completed_at` | timestamp | set on arrival |
| `released_at` | timestamp \| null | set when returned to warehouse via Scan Resources → Return to Warehouse; `null` on next arrival reset |
| `released_by` | string \| null | full name of user who performed return scan; `null` on arrival reset |

**Transfer Lifecycle:**
- On **lock**: `status` → `"Transfer Pending"`
- On **arrival**: `status` → `"Deployed"`, `assigned_project_id` → destination doc id, `location.address` → destination name, `location.type` → `"Project Site"`
- On **return to warehouse**: `released_at` and `released_by` stamped; reset to `null` on next arrival confirmation

---

## 3. resource

**Path:** `/resource/{resource_id}` — created via `.add()`

### Parent Resource Document

| Field | Type | Notes |
|---|---|---|
| `model_code` | string | searchable identifier, e.g. `"CONCRETE_MIXER"` — auto-generated from name |
| `name` | string | UPPERCASE, e.g. `"HEAVY CONCRETE MIXER"` |
| `category` | enum | `"Equipment"`, `"Tools"` |
| `model_number` | string | UPPERCASE; empty string if not provided |
| `total_quantity` | number | total physical items owned |
| `available_quantity` | number | **calculated:** total − (Deployed + Repair + Lost) |
| `running_serial_count` | number | hidden, increment-only; used to generate `-001` suffixes |
| `condition_summary` | map | `{ "New": number, "Functional": number, "Worn": number, "Broken": number }` |
| `status` | string \| absent | `"archived"` (lowercase) when soft-deleted via resource archive action; absent on active records |
| `created_at` / `updated_at` | timestamp | |

### `items` Subcollection

**Path:** `/resource/{resource_id}/items/{unit_id}` — created via transaction

#### Base Fields

| Field | Type | Notes |
|---|---|---|
| `serial_number` | string | e.g. `"CON_MIXER-001"` — generated by Dart logic |
| `condition` | enum | `"New"`, `"Functional"`, `"Worn"`, `"Broken"` |
| `status` | enum | `"In-Storage"`, `"Transfer Pending"`, `"In-Use"`, `"Repair"`, `"Lost"` |
| `assigned_project_id` | DocumentReference \| null | reference to `/project/{id}`; `null` if In-Storage |
| `next_availability` | timestamp \| null | reserved for future ML input |
| `model_number` | string | copied from parent resource |
| `brand_name` | string | |
| `location` | map | `address` (string, UPPERCASE), `type` (`"WAREHOUSE"` \| `"PROJECT SITE"`, UPPERCASE), `geopoint` (GeoPoint \| null) |
| `created_at` / `updated_at` | timestamp | |

#### Transfer Fields (stamped on by approval transaction)

| Field | Type | When |
|---|---|---|
| `transfer_state` | string | `"Transferring"` on lock; `"Completed"` on arrival |
| `transfer_locked` | boolean | `true` on lock; `false` on arrival |
| `transfer_request_id` | string \| null | `resource_requests` doc id; `null` after arrival |
| `transfer_source_site_id` | string | source project doc id |
| `transfer_source_site_name` | string | source project name |
| `transfer_destination_site_id` | string | destination project doc id |
| `transfer_destination_site_name` | string | destination project name |
| `transfer_locked_at` | timestamp | set on lock |
| `transfer_completed_at` | timestamp | set on arrival |
| `released_at` | timestamp \| null | set when item returned to warehouse via Scan Resources → Return to Warehouse; `null` on next arrival reset |
| `released_by` | string \| null | full name of user who performed return scan; `null` on arrival reset |

**Transfer Lifecycle:**
- On **lock**: `status` → `"Transfer Pending"`
- On **arrival**: `status` → `"In-Use"`, `assigned_project_id` → destination ref, `location.address` → destination name, `location.type` → `"Project Site"`

---

## 4. milestone_templates

Presets read at project creation. One document per project category; `milestones` are copied into new project's `milestones` subcollection.

**Path:** `/milestone_templates/{project_type}` — document ID = canonical category ALL CAPS

| Field | Type | Notes |
|---|---|---|
| `project_type` | string | matches document ID |
| `milestones` | array<map> | see structure below |
| `created_at` / `updated_at` | timestamp | |

### milestones[] Entry

| Field | Type | Notes |
|---|---|---|
| `milestone_name` | string | e.g. `"Skom"`, `"RM-01"` |
| `default_weight_percent` | number | this template's share; **all entries sum to 100** |
| `default_target_quantity` | number | default `1` (binary done/not-done) |
| `default_unit` | string | `"pcs"`, `"kgs"`, `"days"`, `"hrs"`, `"sqm"`, `"cum"`, `"lm"`, `"lot"`, `"set"` |
| `order` | number | 0-based display order |

---

## 5. project

**Path:** `/project/{project_id}`

| Field | Type | Notes |
|---|---|---|
| `project_name` | string | UPPERCASE |
| `project_type` | enum | canonical vocabulary UPPERCASE; **selects the `milestone_templates` document** |
| `client_name` | string \| null | e.g. `"Edgepoint"`, `"Smart"`, `"FTAP"` |
| `status` | enum | `"Planned"`, `"On-going"`, `"Completed"`, `"Past Deadline"`, `"Archived"` (system-calculated; `"Archived"` = soft-delete) |
| `budget` | number | |
| `budget_document_url` | string \| null | Firebase Storage download URL of uploaded budget document; absent if no file uploaded. Storage path: `projects/{projectId}/attachments/{filename}` |
| `attachments` | array<map> \| absent | grows via `FieldValue.arrayUnion` on each budget document upload. Each entry: `name` (string), `path` (string — Storage path), `size` (number — bytes), `type` (string — MIME type), `uploaded_at` (number — epoch ms), `url` (string — download URL) |
| `start_date` / `end_date` | timestamp | displayed as "Start Date" / "Forecasted End Date" |
| `overall_progress` | number | **calculated 0–100** — rolled up from milestones; updated on daily-report save |
| `region` | string \| null | top-level field; system-set from coordinates |
| `baselineType` | string | `"milestone"` (default) \| `"weekly"` — determines progress UI and write path |
| `latest_daily_progress` | map \| null | **embedded snapshot** of most recent daily report — updated on every daily-report save |
| `weekly_targets` | array<map> \| null | **only when `baselineType == "weekly"`**; each: `week`, `week_start`, `target_percent`, `actual_percent` |
| `project_manager_id` | DocumentReference \| null | ref → `users/{id}` |
| `project_manager_name` | string \| null | denormalized name |
| `site_engineer_id` | DocumentReference \| null | ref → `users/{id}` |
| `site_engineer_name` | string \| null | denormalized name |
| `personnel` | map | nested: `{ project_manager_id, site_engineer_id }` — used for indexed queries |
| `location` | map | `address` (string, UPPERCASE), `geopoint` (GeoPoint \| null) |
| `created_at` / `updated_at` | timestamp | |

### `project` → `milestones` Subcollection

**Path:** `/project/{project_id}/milestones/{milestone_id}`

| Field | Type | Notes |
|---|---|---|
| `milestone_name` | string | |
| `weight_percent` | number | editable; **all must sum to 100** |
| `target_quantity` | number | editable; default `1` |
| `unit` | enum | `"pcs"`, `"kgs"`, `"days"`, `"hrs"`, `"sqm"`, `"cum"`, `"lm"`, `"lot"`, `"set"` |
| `completed_quantity` | number | **cumulative — source of truth**; updated via `FieldValue.increment()` |
| `completion_percent` | number | **calculated:** `min(100, completed_quantity / target_quantity * 100)` |
| `status` | enum | `"Not Started"`, `"In Progress"`, `"Completed"` |
| `order` | number | 0-based display order |
| `target_date` | timestamp \| null | optional; control point for S-curve planned-progress |
| `actual_completion_date` | timestamp \| null | starts `null`; reserved for actual completion |
| `created_at` / `updated_at` | timestamp | |

### `project` → `daily_progress_reports` Subcollection

**Path:** `/project/{project_id}/daily_progress_reports/{report_date}` — document ID = `YYYY-MM-DD`

| Field | Type | Notes |
|---|---|---|
| `report_date` | timestamp | calendar date of the report |
| `weather` | string | free-text, e.g. `"Sunny, 32°C"` |
| `manpower` | array<map> | crew present that day; each: `{ id (string), name (string), external?: true }` |
| `manpower_display` | string | denormalized comma-joined names |
| `workers_planned` | number | **[ML input]** planned crew count for the day; entered by site engineer at report time |
| `site_access_issues` | number | **[ML input]** `0` = no access issues, `1` = access issues present; toggle entered at report time |
| `milestone_progress` | array<map> | per-milestone log; see structure below |
| `total_completion_percent` | number | snapshot of `project.overall_progress` |
| `created_at` | timestamp | set only on first save |
| `updated_at` | timestamp | always updated |

### milestone_progress[] Entry

| Field | Type | Notes |
|---|---|---|
| `milestone_id` | string | doc id in `milestones` subcollection |
| `milestone_name` | string | denormalized |
| `unit` | string | denormalized |
| `amount_done_today` | number | stepper delta (may be negative on correction) |
| `completed_quantity_after` | number | cumulative snapshot after applying delta |
| `completion_percent_after` | number | snapshot |
| `weight_percent` | number | snapshot |

**Note:** Only milestones with non-zero `amount_done_today` are included.

### `project` → `emergency_reports` Subcollection

**Path:** `/project/{project_id}/emergency_reports/{auto_id}` — created via `.add()`

| Field | Type | Notes |
|---|---|---|
| `date` | timestamp | calendar date of the emergency; **must not be a future date** — enforced on client by capping date picker at `DateTime.now()` |
| `causes` | array<string> | one or more of: `"Adverse Weather"`, `"Material Shortage"`, `"Equipment/Tool Breakdown"`, `"Labor Shortage"`, `"Onsite Accidents"`, `"Other"` |
| `other_cause` | string | free-text description; non-empty only when `causes` contains `"Other"`; empty string otherwise |
| `notes` | string | free-text specifics of the emergency entered by the filer |
| `created_by_uid` | string | uid of the user who filed the report (`UserSession.uid`) |
| `created_by_name` | string | denormalized full name of the filer |
| `created_at` | timestamp | set only on first save |
| `updated_at` | timestamp | always updated on save |

**Access Rules:**
- **View:** all roles (Administrator, Operations Manager, Project Manager, Site Engineer)
- **Create / Update:** Administrator, Site Engineer only

**UI Integration:**
- The `Icons.warning_amber_rounded` triangle on each `ProjectCard` streams this subcollection: red when ≥ 1 report exists, grey when empty. Tapping navigates to `/projects/:id/emergency-reports`.
- The calendar list screen (`EmergencyReportsListScreen`) mirrors the daily-reports calendar format — days with reports are highlighted red.

---

## 6. resource_requests

The request → approval → transfer → assignment flow. No separate assignment document; assignment is the final write of `assigned_project_id` + `status` on arrival.

**Path:** `/resource_requests/{auto_generated_doc_id}` — created via `.add()`

### Base Fields (set at creation)

| Field | Type | Notes |
|---|---|---|
| `request_kind` | string | how request originated, e.g. `"manual"` |
| `resource_category` | enum | `"Manpower"`, `"Equipment"`, `"Tools"` |
| `requested_quantity` | number | units/people |
| `requested_model_id` | string \| null | `/resource` doc id; **required for Equipment/Tools, `null` for Manpower** |
| `requested_model_name` | string \| null | e.g. `"Heavy Concrete Mixer"` |
| `source_site_id` | string \| null | project doc id; `null` until approver assigns |
| `source_site_name` | string \| null | |
| `destination_site_id` | string | requesting project doc id |
| `destination_site_name` | string | |
| `source_resource_id` | string \| null | optional pre-picked resource doc id |
| `source_resource_code` | string \| null | optional pre-picked resource code |
| `requested_by_uid` | string | uid → `users` |
| `requested_by_name` | string | denormalized |
| `requested_by_role` | string | denormalized |
| `status` | enum | `"Pending"` → `"Pending Secondary Approval"` → `"Approved"` → `"Transferring"` → `"Completed"` \| `"Rejected"` |
| `approval_stage` | enum | `"Primary"` → `"Secondary"` → `"Complete"` |
| `transfer_state` | enum | `"Awaiting Approval"` → `"Transferring"` → `"Completed"` \| `"Rejected"` |
| `transfer_locked` | boolean | `false` at creation; `true` once final approval reserves |
| `reserved_resources` | array<map> | empty at creation; populated at final approval |
| `approval_history` | array<map> | empty at creation; appended on each approval |
| `created_at` / `updated_at` | timestamp | |

### Primary Approval Fields

| Field | Type | Notes |
|---|---|---|
| `primary_approval` | map | `{ uid, name, role, approved_at (ISO 8601) }` |
| `source_site_id` / `source_site_name` | string | assigned by approver |

### Final Approval Fields (reserves + locks items)

| Field | Type | Notes |
|---|---|---|
| `secondary_approval` | map | `{ uid, name, role, approved_at (ISO 8601) }` — omitted on Admin bypass |
| `approved_at` | timestamp | |
| `approved_by_uid` | string | |
| `approved_by_name` | string | |
| `approved_by_role` | string | |
| `reserved_resources` | array<map> | populated — see below |

### Rejection Fields

| Field | Type | Notes |
|---|---|---|
| `rejected_at` | timestamp | |
| `rejected_by_uid` | string | |
| `rejected_by_name` | string | |
| `rejected_by_role` | string | |

### Transfer Completion Fields

| Field | Type | Notes |
|---|---|---|
| `completed_at` | timestamp | |
| `completed_by` | string | full name of confirming user |

### approval_history[] Entry

| Field | Type | Notes |
|---|---|---|
| `uid` | string | |
| `name` | string | |
| `role` | string | |
| `approved_at` | string | ISO 8601 |
| `bypass_secondary_approval` | boolean | `true` when Admin skips secondary stage |

### reserved_resources[] — Embedded Assignment Records

Created at final approval; one per locked unit/person.

| Field | Type | Notes |
|---|---|---|
| `kind` | string | `"manpower"` \| `"unit"` |
| `resource_id` | string | `/manpower/{id}` **or** `/resource/{model}/items/{id}` |
| `model_id` | string \| null | parent `/resource` doc id; `null` for manpower |
| `equipment_type` | string \| null | model name, e.g. `"Heavy Concrete Mixer"` |
| `source_site_id` / `source_site_name` | string | |
| `destination_site_id` / `destination_site_name` | string | |
| `display_name` | string | serial number for units, full name for manpower |
| `match_tokens` | array<string> | lowercase scan-match keys: serial, employee id, name, model code |
| `scanned` | boolean | `false` until confirmed at destination |
| `scanned_at` | timestamp \| null | set on scan |
| `scanned_by` | string \| null | full name of confirming user |

### Lifecycle Summary

| Stage | Writer | `status` | `transfer_state` |
|---|---|---|---|
| Request created | `buildResourceRequestDocument` | `"Pending"` | `"Awaiting Approval"` |
| 1st approval | `_approveRequest` (transaction) | `"Pending Secondary Approval"` | `"Awaiting Approval"` |
| Final approval | `_reserveResourcesForApproval` (transaction) | `"Approved"` | `"Transferring"` |
| Item scanned | `_confirmArrival` (transaction) | `"Transferring"` | `"Transferring"` |
| All items scanned | `_finalizeTransfer` | `"Completed"` | `"Completed"` |
| Rejected | `_rejectRequest` | `"Rejected"` | `"Rejected"` |

---

## 7. tool_inventory_logs

**Path:** `/tool_inventory_logs/{auto_id}` — created via `.add()` when site engineer logs daily tool inventory via Scan Resources screen

| Field | Type | Notes |
|---|---|---|
| `site_id` | string | project doc id where tool was scanned |
| `site_name` | string | denormalized project name |
| `serial_number` | string | UPPERCASE — scanned serial number |
| `item_id` | string | `items` doc id under parent resource |
| `item_ref_path` | string | full Firestore path, e.g. `"resource/{id}/items/{id}"` |
| `category` | string | `"Equipment"` or `"Tools"` — from parent resource |
| `model_name` | string | from parent resource |
| `logged_by` | string | full name of scanning user |
| `logged_at` | timestamp | server timestamp |
| `date` | string | `"YYYY-MM-DD"` — calendar date of scan |

**Note:** When tool is scanned for different site than current `assigned_project_id`, item doc is updated: `assigned_project_id` → new project ref, `status` → `"In-Use"`, `location.address` → site address/name, `location.type` → `"Project Site"`.

---

## 8. notifications

**Path:** `/notifications/{user_id}/items/{auto_id}` — created via `.add()`

Each user has their own subcollection of notification items; reads and unread-count streams are scoped per user.

| Field | Type | Notes |
|---|---|---|
| `user_id` | string | uid of the recipient — mirrors the parent document ID |
| `type` | enum | `"message_sent"`, `"tool_requested"`, `"secondary_approval"`, `"request_approved"`, `"project_assigned"`, `"emergency_created"`, `"progress_reported"` |
| `title` | string | short heading shown in the notification panel |
| `body` | string | one-sentence detail line |
| `is_read` | boolean | `false` on creation; set to `true` by `markAsRead` / `markAllAsRead` |
| `metadata` | map | free-form context for deep-linking — e.g. `{ "request_id": "...", "project_id": "..." }` |
| `created_at` | timestamp | server timestamp set on write |

**Access Pattern:**
- `NotificationService.watchNotifications(userId)` streams items ordered by `created_at` descending
- `markAllAsRead` uses a batched write over all documents where `is_read == false`

**Writers:** `NotificationService.sendNotification(...)` — called from screens that trigger relevant events (request submission, approval, project assignment, emergency report creation, message send).

### Notification Metadata by Type

| `type` | Expected `metadata` keys | Notes |
|---|---|---|
| `tool_requested` | `request_id` | Resource request created |
| `secondary_approval` | `request_id` | Secondary approval needed |
| `request_approved` | `request_id` | Request fully approved |
| `project_assigned` | `project_id` | User assigned to project |
| `emergency_created` | `project_id`, `report_id` | Emergency report filed on user's project |
| `progress_reported` | `project_id` | Daily progress submitted (Admins & Operations Managers receive this) |
| `message_sent` | `chat_id` | New chat message |

---

## 9. chats

**Path (chat doc):** `/chats/{chatId}` — chatId pattern: `project_{projectId}` for deterministic lookup

**Path (message doc):** `/chats/{chatId}/messages/{auto_id}` — created via `.add()`

### Chat Document

| Field | Type | Notes |
|---|---|---|
| `members` | string[] | UIDs of all chat members; queried with `array-contains` for the chat list |
| `member_names` | map | `{ uid: displayName }` — cached names for display without extra reads |
| `project_id` | string \| null | ID of the linked project; `null` for a global chat |
| `project_name` | string \| null | Display name of the project |
| `type` | string | `"group"` (only type for now) |
| `last_message` | map | `{ text, sender_name, sent_at }` — used for chat list preview |
| `created_at` | timestamp | server timestamp, set once on creation |
| `updated_at` | timestamp | server timestamp, updated on every message send |

**Membership Rule:** All Administrators and Operations Managers, plus the project's `project_manager_id` and `site_engineer_id` at the time the chat is created. Built by `_createProjectChat()` in `add_project_screen.dart`.

**Eager Creation:** The chat document is created automatically when a project is saved via `doc('project_$projectId').set(...)`. The `chat_room_screen.dart` on-demand path (first-message `WriteBatch`) still acts as a fallback for any project chats not auto-created. If PM or SE is "Unassigned" at project creation, their membership is not included; re-assignment via `edit_project_screen.dart` does not currently update chat members.

### Message Document

| Field | Type | Notes |
|---|---|---|
| `sender_uid` | string | uid of the sender |
| `sender_name` | string | display name of the sender at send time |
| `type` | enum | `"text"`, `"file"`, `"image"` |
| `text` | string \| null | message text; `null` for file-only messages |
| `file_url` | string \| null | Firebase Storage download URL |
| `file_name` | string \| null | original filename |
| `sent_at` | timestamp | server timestamp; messages streamed ordered by `sent_at` ascending |

**Storage Path for Attachments:** `chats/{chatId}/{timestamp}_{filename}`

**Implementation:** `lib/screens/messaging/chat_list_screen.dart`, `lib/screens/messaging/chat_room_screen.dart`

---

## ML Prediction Payload (FastAPI)

Both endpoints (`POST /predict/delay` and `POST /predict/blockers`) share the same `ReportFeatures` request body. Fields are assembled at daily-report save time and sent fire-and-forget after `batch.commit()`.

### Request Mapping

| API Field | Source in App | Stored in Firestore? |
|---|---|---|
| `project_id` | `widget.projectId` | — (document ID) |
| `progress_percent` | `total_completion_percent` | ✅ |
| `planned_progress_percent` | `weekly_targets[n].target_percent`; or linear: `(days_elapsed / total_days) × 100` for milestone projects | — (computed at call time) |
| `days_elapsed` | `report_date − project.start_date` in days | — (computed at call time) |
| `days_remaining` | `project.end_date − report_date` in days | — (computed at call time) |
| `workers_present` | `manpower[].length` | ✅ (via `manpower` array) |
| `workers_planned` | `daily_progress_reports.workers_planned` | ✅ **[NEW]** |
| `equipment_utilization` | `0.0` placeholder | — |
| `weather_severity` | Derived from `weather` text: `0` = sunny/clear, `1` = rain/cloudy, `2` = typhoon/storm | — (computed at call time) |
| `site_access_issues` | `daily_progress_reports.site_access_issues` | ✅ **[NEW]** |

### Response Fields (not yet written to Firestore — future implementation)

**`POST /predict/delay`** returns:
```json
{
  "project_id": string,
  "delay_predicted": bool,
  "delay_probability": number,
  "risk_level": string,
  "confidence": number,
  "top_factors": string[],
  "predicted_at": timestamp
}
```

**`POST /predict/blockers`** returns:
```json
{
  "project_id": string,
  "blocker_type": string,
  "blocker_probability": number,
  "all_probabilities": object,
  "severity": string,
  "top_factors": string[],
  "predicted_at": timestamp
}
```

---

## Calculations & Write Flow

### Milestone Progress Calculation

```
completion_percent = min(100, (completed_quantity / target_quantity) * 100)
```

### Overall Project Progress Calculation

(Weights sum to 100, so result is 0–100)

```
overall_progress = Σ ( milestone.weight_percent * milestone.completion_percent ) / 100
```

### On Submitting Daily Report

Per milestone entry, inside a batch:

```
milestone.completed_quantity  → increment(amount_done_today)   // offline-safe
milestone.completion_percent  → min(100, completed / target * 100)
milestone.status              → "Not Started" | "In Progress" | "Completed"
project.overall_progress      → Σ(weight * completion) / 100
project.latest_daily_progress → full report snapshot (map)
project.weekly_targets[n].actual_percent → overallProgress  // only when baselineType == "weekly"
```

### Editing / Deleting a Logged Entry

```
edit:   milestone.completed_quantity → increment(new_amount - old_amount)
delete: milestone.completed_quantity → increment(-old_amount)
```

### Validation

- Before saving milestone edits: enforce `weight_percent` across all milestones sums to 100
- Cap `completion_percent` at 100

---

## Cross-collection Reference Map

| From | Field | → To |
|---|---|---|
| `project` | `project_manager_id`, `site_engineer_id` | `users/{id}` |
| `project` | `project_type` | `milestone_templates/{project_type}` |
| `daily_progress_reports.manpower[]` | `id` | `manpower/{id}` |
| `daily_progress_reports.milestone_progress[]` | `milestone_id` | `project/{id}/milestones/{id}` |
| `manpower` / `resource items` | `assigned_project_id` | `project/{id}` |
| `manpower` / `resource items` | `transfer_request_id` | `resource_requests/{id}` |
| `resource_requests` | `destination_site_id`, `source_site_id` | `project/{id}` |
| `resource_requests` | `requested_model_id` | `resource/{id}` (Equipment/Tools only) |
| `resource_requests` | `requested_by_uid`, approval `uid`s | `users/{id}` |
| `resource_requests.reserved_resources[]` | `resource_id` | `manpower/{id}` **or** `resource/{model}/items/{id}` |
| `tool_inventory_logs` | `site_id` | `project/{id}` |
| `tool_inventory_logs` | `item_ref_path` | `resource/{id}/items/{id}` |
| `notifications.items[].metadata` | `request_id` | `resource_requests/{id}` |
| `notifications.items[].metadata` | `project_id` | `project/{id}` |
| `notifications.items[].metadata` | `chat_id` | `chats/{id}` |
| `chats.member_names` | uid keys | `users/{id}` |
| `chats.messages[].sender_uid` | string | `users/{id}` |
| `manpower.specialization` ↔ `project.project_type` | matching | same canonical vocabulary |

---

## Role Permissions

### Create / Manage Projects & Milestones
- Administrator
- Operations Manager
- Project Manager (web / CI4)

### Submit Daily Progress Reports
- Site Engineer (mobile / Flutter, offline)
- Also has access to: Scan Resources (confirm arrival + log inventory)

### Resource Requests & Approvals
- **Raise requests:** Project Manager / Site Engineer
- **Primary approval & source assignment:** Administrator, Operations Manager, Project Manager
- **Secondary approval:** same three roles — but secondary approver's role must differ from primary approver's role (enforced in `_RequestDetailScreenState._approveRequest`)
- **Admin bypass:** Administrator can skip secondary approval entirely via a toggle at the primary stage

### Manage Core Data
- **`resource`, `manpower`, `users`:** Administrator / Operations Manager

### Emergency Reporting
- **View:** all roles
- **Create / Update:** Administrator, Site Engineer only

### Notifications
- **Send:** `NotificationService` (triggered from app events)
- **Read:** user receives their own notifications; stream scoped per user

### Chats
- **Create:** Auto-created when project is saved; retained by first-message `WriteBatch` fallback
- **Send messages:** All members (fetched from `members` array)
- **View:** Members of the chat only

---

## Key Implementation Notes

1. **Offline Support:** Mobile Flutter app caches data; `FieldValue.increment()` ensures queued writes merge correctly on reconnection
2. **Transactions:** All concurrent mutations use Firestore transactions to prevent race conditions
3. **Denormalization:** Display fields (names, addresses, etc.) are denormalized for performance; always reference source-of-truth fields for updates
4. **Status Casing:** Mix of UPPERCASE, Title Case, and lowercase per field origin; always compare case-insensitively in queries
5. **Soft-Delete:** Records marked `status: "archived"` (lowercase); filtered out at read time in UI queries
6. **Transfer Lifecycle:** Two-phase: lock → approval, then scan/arrival → finalize; all tracked via transfer fields
7. **ML Integration:** `workers_planned` and `site_access_issues` are new ML input fields; reserved response fields not yet written to Firestore
8. **Temp Password Flow:** All users start with `must_change_password: true` and a 15-min temp password window; forced password change on first login
9. **Profile Photos & Attachments:** Firebase Storage uploads for both user profiles and project budget documents; download URLs stored in Firestore
10. **Return to Warehouse:** New transfer state tracked via `released_at` and `released_by` on both manpower and resource items; reset to `null` on next arrival confirmation
11. **Emergency Reports:** Real-time safety alerts at project level; calendar-based UI mirrors daily-reports pattern; restricted to Admin and Site Engineer for writes
12. **Notifications:** Per-user subcollection (`/notifications/{user_id}/items/{auto_id}`) for scalability; fired on resource requests, approvals, project assignments, emergency reports, and daily progress submissions
13. **Chat System:** Deterministic chatId pattern (`project_{projectId}`) for project-specific group chats; eager creation at project save time with fallback on first message; members fetched from `members` array

---

## Document Versions

| Version | Date | Key Changes |
|---|---|---|
| Current | June 2026 | Added notifications and chats collections with role-based access and metadata structures |
| v3 | June 2026 | Added emergency_reports subcollection with safety alert UI integration |
| v2 | June 2026 | Added ML fields, temp-password flow, soft-delete archiving, profile/attachment URLs, released_at/released_by |
| v1 | — | Initial schema with core collections |

