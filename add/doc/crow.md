# CROW: Closured Row Operation Window

**A Minimalist Paradigm for Functional, View-Scoped Database Interaction**

CROW defines a database-first, function-driven architecture based on three principles:

1. **The closure holds behavior**
2. **The database view holds truth**
3. **The schema defines scope**

Arrow is the practical implementation of this paradigm: a flag-driven closure that encapsulates a single row's lifecycle, limited by what the schema exposes, and reactive only to explicit commands.

---

## What is CROW?

**CROW** stands for **Closured Row Operation Window**. It represents a minimal behavioral unit that:

* Encapsulates a single **row** of data
* Operates via **bitflag-driven commands** (LOAD, SET, SAVE, etc.)
* Is implemented as a **closure**, not an object
* Functions within a **window** of access defined by a database **view**

This window constrains what is **visible**, **editable**, and **meaningful**, delegating relationship logic and permission control to the database.

---

## Core Design Principles

### 1. **Closure over Class**

CROW uses closures to encapsulate per-row state, avoiding the overhead and coupling of object-oriented modeling. Each closure:

* Carries internal state (loaded data, edits, schema)
* Reacts to bitflag commands (e.g. `ROW_LOAD | ROW_GET`)
* Operates as a deterministic, testable function

### 2. **The Schema is the Contract**

Instead of field-level validation or permission logic in application code, CROW delegates access control to the **view schema**:

* Fields in the schema : editable (`ROW_EDIT`)
* Fields outside the schema : ignored (`ROW_MORE`)
* Constraints (e.g. row ownership) enforced via SQL logic

The application only speaks to the view; it never infers permissions.

### 3. **Views as Behavioral Windows**

Each view defines a "window" into a row:

* What can be seen (selected fields)
* What can be changed (editable fields)
* What can be acted upon (computed fields, state transitions)

The application doesn't define roles or policies — views do.

---

## The CROW Execution Flow

```php
$crow = row(db(), 'user_profile_edit');
$crow(ROW_LOAD, ['id' => 5]);
$crow(ROW_SET, ['name' => 'Jane']);
crow(ROW_SAVE);
$data = $crow(ROW_GET);
```

All state is internal. All operations are explicit. All permission is enforced structurally.

---

## Usage Examples

### 1. Step-by-step usage with separate flags

```php
$profile = row(db(), 'user_profile_edit');

$profile(ROW_LOAD, ['id' => 42]);           // Load the row
$profile(ROW_SET, ['name' => 'Jane']);      // Set new field value
$profile(ROW_SAVE);                         // Save changes
$data = $profile(ROW_GET);                  // Get updated row state
```

### 2. Combined operations using bitwise flags

```php
$form_data = row(db(), 'user_profile_edit')(ROW_LOAD|ROW_GET, ['id' => 42]);
// render form with $form_data
```
Then when the user submits the form:
```php
$orignal = $profile(ROW_LOAD, ['id' => 42]);           // Load the row
$profile(ROW_SET | ROW_SAVE, $_POST); 
```
If the user ONLY changed the name to "Jane", the executed SQL would be:
```sql
UPDATE `user_profile` SET `name` = 'Jane' WHERE `id` = 42;
```
>Only the modified values are included in the SQL update.
>
>If the $_POST data does not alter the original data, no SQL is executed.
>
> If the $_POST data contains fields not in the schema, they are ignored in the query, but can be retrieved later with `ROW_GET|ROW_MORE`.


### 3. API usage pattern with error handling

```php
$crow = row(db(), 'user_profile_edit');

try {
    $crow(ROW_LOAD, ['id' => $_POST['id']]);
    $crow(ROW_SET, $_POST);
    $crow(ROW_SAVE);
    echo json_encode($crow(ROW_GET));
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
```

---

## Benefits of the CROW Paradigm

| Concern            | Traditional ORM            | CROW Paradigm               |
| ------------------ | -------------------------- | --------------------------- |
| Relationship Logic | Simulated in code          | Expressed in SQL views      |
| Editable Fields    | Guarded with policies      | Defined by schema           |
| Access Control     | Checked per request        | Encoded in views            |
| Performance        | Variable, over-abstracted  | Native query plans          |
| Complexity         | Inherited from model graph | Explicit, per-row, isolated |
| Transparency       | Hidden by abstraction      | Exposed and testable        |
| Deletion           | Built-in, risky            | Manual, intentional         |

---
