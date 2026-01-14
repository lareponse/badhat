# ARROW View Creation Guideline

Only write views that eliminate application logic. If it doesn't reduce code, it's not a CROW-compliant view.

---

## 1. Edit Views

**Purpose**: Define exactly what can be changed, and by whom.
**Pattern**: `entity_edit_context`

Include:

* `id`
* Only editable fields for that role/context

Avoid:

* Joins
* Computed fields
* Redundant or read-only columns

Replaces:

* Field whitelisting
* Role-based field filtering
* PATCH sanitization logic

---

## 2. Join-Relation Views

**Purpose**: Provide pre-joined foreign key display fields.
**Pattern**: `entity_one_target`, `entity_many_target`

Include:

* `target_id`, `target_slug`, `target_label`
* All fields prefixed with the joined table name

Avoid:

* Nested or recursive joins
* Unqualified column names

Replaces:

* Join logic in controllers
* Label resolution lookups
* Hydration or mapping steps

---

## 3. Access-Controlled Views

**Purpose**: Enforce row-level visibility in SQL.
**Pattern**: `entity_many_accessible`, `entity_one_visible`

Include:

* Only rows the current user may see
* Joins to permissions or ownership tables

Avoid:

* Duplicating access checks in PHP
* Application-side filtering after load

Replaces:

* ACL middleware
* Ownership checks in code
* Per-record visibility filters

---

## 4. Computed Logic Views

**Purpose**: Push derived or dynamic business logic into SQL.
**Pattern**: `entity_one_status`, `entity_one_logic`, `entity_many_metrics`

Include:

* Tiers, flags, or computed fields
* Status indicators derived from multiple fields

Avoid:

* Business logic duplication in PHP
* Deriving logic in template or controller

Replaces:

* Status mappers
* Conditionals spread across UI and backend
* Ad hoc calculations

---

## General Rules

* One view = one purpose
* View schema = security boundary
* No inferred permissions; no app-side filtering
* Prefix all joined fields to avoid ambiguity
* Use `ROW_SCHEMA`, `ROW_EDIT`, and `ROW_MORE` to verify design

---

## Naming Conventions

| Type          | Example                |
| ------------- | ---------------------- |
| Edit View     | `user_edit_profile`    |
| Relation View | `post_one_author`      |
| ACL View      | `file_many_accessible` |
| Logic View    | `order_one_status`     |

---

## Testing Checklist

| Condition                         | Method                                   |
| --------------------------------- | ---------------------------------------- |
| Editable fields correctly exposed | `row(view)(ROW_EDIT)`                    |
| Extra fields correctly excluded   | `row(view)(ROW_MORE)`                    |
| Access rules enforced by view     | Query as unauthorized user               |
| Computed logic stable             | Assert SQL logic against expected values |

---

Views should replace code. If they don’t, delete them.
