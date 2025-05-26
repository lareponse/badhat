# Introduction

`pdo(...$args)` is your single-entry database gateway: no factories, no managers, no ceremony. 

It unites four distinct responsibilities (connection, raw access, querying, transaction) into a lean, statically cached function. 

Critics will decry the amalgamation of concerns—but true minimalism isn’t about purity; it’s about ruthless efficiency. 

Welcome to Occam’s Razor in PHP: one function, four clear modes, zero wasted keystrokes.



# Table of Contents

1. [Connection Mode](#1-connection-mode)
2. [Native Mode](#2-native-mode)
3. [Query Mode](#3-query-mode)
4. [Transaction Mode](#4-transaction-mode)


# 1. Connection Mode

**Signature**

```php
pdo(string $dsn, string $user = null, string $pass = null, array $options = []): PDO
```

> **Note**: arguments mirror exactly `PDO::__construct(string $dsn, string $username = null, string $password = null, array $options = [])`.

## Parameters

| Name      | Type     | Required | Description & Justification                                                                                                                                                             |
| --------- | -------- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `dsn`     | `string` | yes      | Data Source Name. Throws `LogicException('Empty DSN')` if empty—catch misconfigs early.                                                                                                 |
| `user`    | `string` | no       | Database username.                                                                                                                                                                      |
| `pass`    | `string` | no       | Database password.                                                                                                                                                                      |
| `options` | `array`  | no       | Merged with defaults (`PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`, `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`). Enables centralized best practices with override flexibility. |

## Return Value

* **`PDO`**: a singleton instance (first call constructs, subsequent no-arg calls return same).

## Errors / Exceptions

* **Empty DSN**:

  ```php
  throw new LogicException('Empty DSN');
  ```
* **PDOException**:
  Any driver/option error per `ERRMODE_EXCEPTION`.

---

# 2. Native Mode

**Signature**

```php
pdo(): PDO
```

## Parameters

* *none*

## Return Value

* **`PDO`**: returns the previously instantiated singleton.

## Errors / Exceptions

* **Empty DSN** on first-ever call (falls back to Connection Mode).

---

# 3. Query Mode

**Signature**

```php
pdo(string $sql, array $bindings = [], PDO $connection = null): PDOStatement
```

## Parameters

| Name         | Type     | Required | Description & Justification                                        |
| ------------ | -------- | -------- | ------------------------------------------------------------------ |
| `sql`        | `string` | yes      | SQL to execute. One-liner `prepare`+`execute` slashes boilerplate. |
| `bindings`   | `array`  | no       | Positional or named parameters.                                    |
| `connection` | `PDO`    | no       | Override singleton for multi-DB contexts.                          |

## Return Value

* **`PDOStatement`**: always returned (exceptions handle failures).

## Errors / Exceptions

* **PDOException**: on SQL syntax errors or bind mismatches.

---

# 4. Transaction Mode

**Signature**

```php
pdo(callable $work, PDO $connection = null)
```

## Parameters

| Name         | Type       | Required | Description & Justification                            |
| ------------ | ---------- | -------- | ------------------------------------------------------ |
| `work`       | `callable` | yes      | Encapsulate multiple `pdo(...)` calls.                 |
| `connection` | `PDO`      | no       | Override singleton for special transactional contexts. |

## Return Value

* **`mixed`**: whatever `$work()` returns.

## Errors / Exceptions

* **Throwable** inside `$work`: triggers rollback then rethrows.
* **PDOException**: on `beginTransaction()`/`commit()` failures, if any.

---


# Combined Usage Example

```php
<?php
// 1. Bootstrap (Connection Mode)
pdo(
    'mysql:host=127.0.0.1;dbname=app;charset=utf8', 
    'user', 
    'pass',
    [PDO::ATTR_TIMEOUT => 5]
);

// 2. Raw PDO access (Native Mode)
$raw = pdo(); 

// 3. Simple Query (Query Mode)
$users = pdo(
    'SELECT id, name FROM users WHERE active = ?', 
    [1]
)->fetchAll();

// 4. Transaction (Transaction Mode)
$result = pdo(function() {
    // debit
    pdo('UPDATE accounts SET balance = balance - ? WHERE id = ?', [100, 1]);
    // credit
    pdo('UPDATE accounts SET balance = balance + ? WHERE id = ?', [100, 2]);
    // fetch new balances
    return pdo('SELECT id, balance FROM accounts WHERE id IN (?, ?)', [1, 2])->fetchAll();
});

// Inspect results
var_dump($users, $result);
```
