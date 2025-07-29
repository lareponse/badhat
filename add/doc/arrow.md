# ARROW: Bitwise Row Operations

**Single-row database operations using bitwise behavior flags**

---

## Core Concept

Arrow encapsulates a single database row's lifecycle in a closure, controlled by bitwise flags. Each flag represents a specific operation or data state.

```php
$article = row(db(), 'article');
$article(ROW_LOAD, ['slug' => 'how-to-php']);
$article(ROW_SET, ['title' => 'Updated Title']);
$article(ROW_SAVE);
```

---

## Bitwise Flags

### State Management
* **`ROW_LOAD (1)`** - Load row from database, set schema
* **`ROW_SCHEMA (2)`** - Manage column definitions
* **`ROW_EDIT (4)`** - Valid alterations (in schema)
* **`ROW_MORE (8)`** - Auxiliary data (outside schema)

### Operations
* **`ROW_SAVE (16)`** - Persist changes to database
* **`ROW_SET (32)`** - Apply data to internal state
* **`ROW_GET (64)`** - Retrieve data from internal state
* **`ROW_ERROR (128)`** - Access error state
* **`ROW_RESET (256)`** - Clear internal state

### Compound Operations
* **`ROW_CREATE`** - `ROW_SCHEMA | ROW_SET | ROW_SAVE`
* **`ROW_UPDATE`** - `ROW_LOAD | ROW_SET | ROW_SAVE`

---

## Basic Usage

### Load and Display
```php
$article = row(db(), 'article');
$form_data = $article(ROW_GET | ROW_LOAD, ['slug' => 'how-to-php']);
```

### Update Existing
```php
$article(ROW_LOAD, ['slug' => 'how-to-php']);
$article(ROW_SET, ['title' => 'New Title', 'published_at' => date('Y-m-d H:i:s')]);
$article(ROW_SAVE);
```

### Compound Update
```php
$article(ROW_UPDATE, ['id' => 42, 'title' => 'New Title']);
// Equivalent to: LOAD by id, SET title, SAVE
```

### Create New
```php
row(db(), 'article')(ROW_CREATE, ['title' => 'New Article', 'content' => 'Content']);
```

---

## Data Separation

Arrow automatically sorts incoming data based on schema:

```php
$article(ROW_SET, [
    'title' => 'Valid field',           // → ROW_EDIT (in schema)
    'article_tags' => ['php', 'web']    // → ROW_MORE (outside schema)
]);
```

### Retrieving Data
```php
$all_data = $article(ROW_GET);                    // LOAD + EDIT merged
$valid_only = $article(ROW_GET | ROW_EDIT);       // Only schema fields
$extra_only = $article(ROW_GET | ROW_MORE);       // Only auxiliary data
$everything = $article(ROW_GET | ROW_LOAD | ROW_EDIT | ROW_MORE);
```

### Single Field Access
```php
$title = $article(ROW_GET, ['title']);             // Returns string, not array
```

---

## Schema Management

### Automatic Schema
Schema is set automatically when loading a row:
```php
$article(ROW_LOAD, ['slug' => 'how-to-php']);
$schema = $article(ROW_GET | ROW_SCHEMA);          // Array of column names
```

### Manual Schema
```php
$article(ROW_SCHEMA | ROW_SET, ['slug', 'title', 'content', 'published_at']);
```

### Schema Introspection
```php
$article(ROW_SCHEMA | ROW_SET);                    // Uses select_schema() function
```

---

## Force Data Placement

Override automatic schema sorting:

```php
$article(ROW_SET | ROW_EDIT, ['published_at' => date('Y-m-d H:i:s')]);  // Force to EDIT
$article(ROW_SET | ROW_MORE, ['subscription_consent' => date('Y-m-d H:i:s')]);  // Force to MORE
```

**Note**: `ROW_MORE` data is never saved to database.

---

## SQL Generation

Arrow only generates SQL for changed values:

```php
$article(ROW_LOAD, ['slug' => 'how-to-php']);     // Loads: title='How to PHP', published_at=NULL
$article(ROW_SET, ['title' => 'How to PHP']);     // No change - same value
$article(ROW_SET, ['published_at' => '2023-10-01 12:00:00']);
$article(ROW_SAVE);
// SQL: UPDATE `article` SET `published_at` = '2023-10-01 12:00:00' WHERE `slug` = 'how-to-php';
```

---

## Error Handling

```php
try {
    $article(ROW_SAVE);
} catch (Throwable $e) {
    // Error automatically captured in internal state
}

$error = $article(ROW_GET | ROW_ERROR);            // Returns Throwable or null
```

---

## State Reset

```php
$article(ROW_RESET);                               // Clear all internal state except table/pk
```

---

## Performance Patterns

### One-shot Operations
```php
// No intermediate variables
row(db(), 'article')(ROW_CREATE, $post_data);
```

### Reusable Closures
```php
$article = row(db(), 'article');
// Reuse $article for multiple operations
```

### Compound Flags
```php
// Single call instead of three separate calls
$article(ROW_LOAD | ROW_SET | ROW_SAVE, ['id' => 42, 'title' => 'New Title']);
```

---

## Return Values

* **Most operations**: Return internal state array
* **`ROW_GET` operations**: Return requested data
* **`ROW_GET | ROW_SCHEMA`**: Return schema array
* **`ROW_GET | ROW_ERROR`**: Return error or null
* **Single field access**: Return field value directly

---

## Best Practices

1. **Load before update**: Always `ROW_LOAD` before `ROW_SET` for updates
2. **Use compound operations**: `ROW_UPDATE` and `ROW_CREATE` for common patterns
3. **Check errors**: Always handle `ROW_ERROR` after `ROW_SAVE`
4. **Schema-first**: Let schema determine what gets saved vs. auxiliary data
5. **Reuse closures**: Create once, operate multiple times

Arrow provides precise control over single-row operations while maintaining the BADHAT philosophy of explicit, bitwise-controlled behavior.