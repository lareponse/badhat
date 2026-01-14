# ARROW

**Single-row database operations using bitmask driven closures**

This closure-based row context turns each database record into its own mini state machine, loading, mutating, validating, and persisting in a unified, predictable flow. 

## Key Advantages

1. **Zero object overhead** - No model instantiation, hydration, or magic methods
2. **Predictable SQL** - You know exactly what queries run and when
3. **Minimal memory** - Closure state vs. full object graphs
4. **Explicit control** - Bitwise flags make behavior completely transparent
5. **Change detection** - Automatic, but only for what actually changed

**Arrow is perfect when you need both speed and precise control over database interactions**

## Core Concept

Arrow encapsulates a single database row's lifecycle in a closure, controlled by bitwise flags. Each flag represents a specific operation or data state.

```php
$article = row(db(), 'article');
$article(ROW_LOAD, ['slug' => 'how-to-php']);
$article(ROW_SET, ['title' => 'Updated Title']);
$article(ROW_SAVE);
```

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

### Compound Create
```php
$post_data = ['title' => 'New Article', 'content' => 'Content','slug' => 'new-title'];

// closure then creation
$article = row(db(), 'article');
$article(ROW_CREATE, $post_data);

// Or, as a one-liner 
row(db(), 'article')(ROW_CREATE, $post_data);
```

### Compound Update
```php
// Equivalent to: LOAD by id, SET title, content (skip slug), SAVE (use default unique field 'id')
$clean_data = ['id' => 42, 'content' => 'Content with extra', 'slug' => 'new-title'];

$article = row(db(), 'article');
$article(ROW_UPDATE, $clean_data);

// Use custom unique field 'slug'
$clean_data = ['slug' => 'new-title', 'title' => 'New Title'];

$article = row(db(), 'article', 'slug');
$article(ROW_UPDATE, $clean_data);

// Or as one-liner
row(db(), 'article', 'slug')(ROW_UPDATE, $clean_data);
```


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
$valid_data = $article(ROW_GET);                 // LOAD + EDIT merged
$edit_only = $article(ROW_GET | ROW_EDIT);       // Only schema fields
$more_only = $article(ROW_GET | ROW_MORE);       // Only auxiliary data
$everything = $article(ROW_GET | ROW_LOAD | ROW_EDIT | ROW_MORE);
```

### Field Access
```php
$subset = $article(ROW_GET, ['slug', 'title']);    // Returns  array

$title = $article(ROW_GET, ['title']);             // Returns string, not array
```

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
// should be view based, but you do you
```

### Schema Introspection
```php
$article(ROW_SCHEMA | ROW_SET);                    // Uses select_schema() function
// mostly for inserts
```


## Force Data Placement

Override automatic schema sorting:

```php
$article(ROW_SET | ROW_EDIT, ['published_at' => date('Y-m-d H:i:s')]);  // Force to EDIT
$article(ROW_SET | ROW_MORE, ['subscription_consent' => date('Y-m-d H:i:s')]);  // Force to MORE
```

**Note**: `ROW_MORE` data is never saved to database.


## SQL Generation

Arrow only generates SQL for changed values:

```php
$article(ROW_LOAD, ['slug' => 'how-to-php']);     // Loads: title='How to PHP', published_at=NULL
$article(ROW_SET, ['title' => 'How to PHP', 'published_at' => '2023-10-01 12:00:00']); 
$article(ROW_SAVE);
// SQL: UPDATE `article` SET `published_at` = '2023-10-01 12:00:00' WHERE `slug` = 'how-to-php';
```


## Error Handling
Arrow captures all exceptions in internal state
```php
$article(ROW_SAVE);                             // Error automatically captured in internal state
if($error = $article(ROW_GET | ROW_ERROR)){     // Returns Throwable or null
    // error handling
}
```


## State Reset

```php
$article(ROW_RESET);                               // Clear all internal state except table/pk
```


## Performance Patterns

### One-shot Operations
```php
// No intermediate variables
row(db(), 'article')(ROW_CREATE, $post_data);
```

### Pool closures for bulk operations
```php
$article = row(db(), 'article');
foreach ($bulk_data as $data) {
    $article(ROW_CREATE | ROW_RESET, $data);
}
```


## Return Values

* **Most operations**: Return internal state array
* **`ROW_GET` operations**: Return requested data
* **`ROW_GET | ROW_SCHEMA`**: Return schema array
* **`ROW_GET | ROW_ERROR`**: Return error or null
* **Single field access**: Return field value directly


## Best Practices

1. **Load before update**: Always `ROW_LOAD` before `ROW_SET` for updates
2. **Use compound operations**: `ROW_UPDATE` and `ROW_CREATE` for common patterns
3. **Check errors**: Always handle `ROW_ERROR` after `ROW_SAVE`
4. **Schema-first**: Let schema determine what gets saved vs. auxiliary data
5. **Reuse closures**: Create once, operate multiple times

Arrow provides precise control over single-row operations while maintaining the BADHAT philosophy of explicit, bitwise-controlled behavior.



## Bitwise Flags

| State        | Value | Purpose                           |
| ------------ | ----- | --------------------------------- |
| `ROW_LOAD`   | `1`   | Loaded row from database          |
| `ROW_SCHEMA` | `2`   | Manage column definitions         |
| `ROW_EDIT`   | `4`   | Valid alterations (within schema) |
| `ROW_MORE`   | `8`   | Auxiliary data (outside schema)   |
| `ROW_ERROR`  | `128` | Error state                       |


| Operations  | Value | Purpose                               |
| ----------- | ----- | ------------------------------------- |
| `ROW_LOAD`  | `1`   | Load row from database and set schema |
| `ROW_SAVE`  | `16`  | Persist changes to database           |
| `ROW_SET`   | `32`  | Apply data to internal state          |
| `ROW_GET`   | `64`  | Retrieve data from internal state     |
| `ROW_RESET` | `256` | Clear internal state                  |

| Constant     | Components    |Purpose    |
| ------------ | ------------- | ----------|
| `ROW_CREATE` | `ROW_SCHEMA \| ROW_SET \| ROW_SAVE` | Create new row, populate with schema and save |
| `ROW_UPDATE` | `ROW_LOAD \| ROW_SET \| ROW_SAVE`   | Load existing row, apply changes, save    |


