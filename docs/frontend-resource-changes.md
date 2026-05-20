Frontend Checklist — Resource (Equipment / Tools)

This file lists the first three frontend-only tasks required to support the updated resource schema.

1) Add `model_code` input field
- Purpose: searchable identifier for the resource (e.g. CONCRETE_MIXER).
- UI: visible on create/edit resource forms for Equipment/Tools.
- Input name: `model_code` (string)
- Validation (client-side): required, pattern: `^[A-Z0-9_\-]+$` (recommend uppercase, digits, underscores, hyphens)
- Example placeholder: "CONCRETE_MIXER"

2) Update item forms to use new item fields
- Replace legacy `unit_id` with `serial_number` where applicable.
- Item fields to add/update:
  - `serial_number` (string) — displayed, but can be empty to trigger auto-generation server-side.
  - `condition` (select) — options: `New`, `Functional`, `Worn`, `Broken`.
  - `status` (select) — options: `In-Storage`, `In-Use`, `Repair`, `Lost`.
  - `assigned_project_id` (select or hidden reference) — optional; null if in storage.
  - `next_availability` (datetime) — optional; use ISO datetime input.
  - `location.address` (text), `location.type` (select: `Warehouse`, `Project Site`).
  - Optional: `location.geopoint.lat`, `location.geopoint.lng` (number inputs or map picker).
- Input naming conventions in forms: use `items[<index>][serial_number]`, `items[<index>][condition]`, etc.
- Client-side validation suggestions:
  - `condition`: required, must be one of the allowed values.
  - `status`: required, must be one of the allowed values.
  - `assigned_project_id`: optional, must be a valid project id when present.

3) Remove deprecated top-level `status` field for Equipment/Tools
- On Equipment/Tools forms, remove or hide any top-level `status` input.
- Item-level `status` now represents each unit's lifecycle/state.
- If the UI needs an aggregate status, compute it from item statuses or show `available_quantity`/`total_quantity`.

Notes & Implementation Tips
- Keep `asset_details` fields in the forms hidden if you need backward compatibility with existing views; they can be derived from `items`.
- Use consistent naming for inputs to ensure the controller's `normalizeItems()` receives `items` as an array.
- Provide a button to "Add specific units now" that reveals item inputs; otherwise create resource without items and let users add items later.

