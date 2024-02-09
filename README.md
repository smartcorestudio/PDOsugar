# PDOsugar
**PDOsugar is a PDO wrapper that simplifies database CRUD operations with PHP**

This library is based on David Carr's [PDO Wrapper](https://github.com/dcblogdev/pdo-wrapper)

PDOsugar helps to make your database-related code:
* Shorter
* Simplier
* Safer

Let's imagine you need to insert and read some data:
```php
// Data to insert
$data = [
    'name' => 'John Doe',
    'email' => 'name@domain.com',
    'sex' => 'm',
    'role' => 1
];

```
With standard PDO you'll do it like this:
```php
// PDO - inserting a record
$sql = "INSERT INTO users (name, email, sex, role) VALUES (:name, :surname, :sex, :role)";
$pdo->prepare($sql)->execute($data);

// PDO - reading a record
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute(['id' => 42]); 
$user = $stmt->fetch();
```
With PDOsugar you can write just:
```php
// PDOsugar - inserting a record
$db->insert('users', $data);

// PDOsugar - reading a record
$user = $db->record('users', 42);
```
Sweet, right?

At the same time PDOsugar, unlike most of ORMs, allows using standard SQL syntax when selecting data 

&nbsp;

## Installation
🎉You don't need Composer to use this library! Just include/require `PDOsugar.php` and and you're all set! So easy!

Set the DB credentials. Finally, create an instance of the classes.

```php
require 'PDOsugar.php';

// make a connection to mysql here
$options = [
    //required
    'username' => '',
    'database' => '',
    //optional
    'password' => '',
    'type' => 'mysql',
    'charset' => 'utf8',
    'host' => 'dev',
    'port' => '3309'
];

$db = new Database($options);
```
&nbsp;

## Accessing PDO
You can call `getPdo()` to get access to PDO directly:

```php
$db->getPdo()
```

This allows to chain calls:

```php
$db->getPdo()->query($sql)->fetch();
```
&nbsp;

## R - Reading data

All queries use prepared statements, calling ->run() returns a PDO option that can be chained:

**run()** - the most versatile way of reading data
```php
$db->run("SELECT * FROM users")->fetchAll();
```


### Placeholders
To select records based on user data instead of passing the data to the query directly use a prepared statement, this is safer and stops any attempt at SQL injections.

**Using named and anonymous placeholders:**

```php
//Named placeholders - base way
$db->run("SELECT * FROM users WHERE id = :id", ['id' => 1])->fetch();

//Named placeholders - alternative way
$params['id'] = 1;
$db->run("SELECT * FROM users WHERE id = :id", $params)->fetch();

//Annonomus placeholders
$db->run("SELECT * FROM users WHERE id = ?", 1)->fetch();

```


### Shortcut functions
---
**rows($sql, $args=[])** - returns array of rows as objects
> (See [PDO::FETCH_OBJ](https://phpdelusions.net/pdo/fetch_modes#FETCH_OBJ))
```php
$db->rows("SELECT * FROM users");
// This equals to
$pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_OBJ);
```
---

**rowsArray($sql, $args=[])** - returns array of rows as arrays
> (See [PDO::FETCH_ASSOC](https://phpdelusions.net/pdo/fetch_modes#FETCH_ASSOC))
```php
$db->rows("SELECT * FROM users");
// This equals to
$pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
```
---
**rowsJSON($sql, $args=[])** - returns JSON string

```php
$db->rowsJSON("SELECT * FROM users");
// This equals to
json_encode($pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
```
---

**rowsList($sql, $args=[])** - returns array of rows (as arrays), indexed by the first field
> (see [PDO::FETCH_UNIQUE](https://phpdelusions.net/pdo/fetch_modes#FETCH_UNIQUE))
```php
$db->rowsList("SELECT * FROM users");
// This equals to
$pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_UNIQUE);
```
---

**rowsGroup($sql, $args=[])** - returns nested array of rows (as arrays), grouped by the first field
> Use `rowsGroup()` to group rows from a result set by the first selected column (see [PDO::FETCH_GROUP](https://www.phptutorial.net/php-pdo/pdo-fetch_group/))
```php
$db->rowsGroup("SELECT role, name, email FROM users");
// This equals to
$pdo->query("SELECT role, name, email FROM users")->fetchAll(PDO::FETCH_GROUP);
```
---

**rowsColumnList($sql, $args=[])** - returns array of second field values, indexed by the first field
> Use `rowsColumnList()` to fetch the two-column result in an array where the first column is the key and the second column is the value (see [PDO::FETCH_KEY_PAIR](https://www.phptutorial.net/php-pdo/pdo-fetch_key_pair/)).
```php
$db->rowsColumnList("SELECT id, name FROM users");
// This equals to
$pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_KEY_PAIR);
```
---

**rowsColumn($sql, $args=[])** - returns array of field values
> Use `rowsColumn()`to get plain one-dimensional array if only one column being fetched. (see [PDO::COLUMN](https://phpdelusions.net/pdo/fetch_modes#FETCH_COLUMN)
```php
$db->rows("SELECT name FROM users");
// This equals to
$pdo->query("SELECT name FROM users")->fetchAll(PDO::COLUMN);
```
---

**row($sql, $args=[])** - returns one row using SQL query
```php
$user = $db->row("SELECT * FROM users WHERE email=:email", ['email' => 'name@domain.com']);
// This equals to
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=:email");
$stmt->execute(['email' => 'name@domain.com']); 
$user = $stmt->fetch();
```
---

**record($table, $id)** - returns one row from table by id
```php
$user = $db->record('users', 42);
// This equals to
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute(['id' => 42]); 
$user = $stmt->fetch();
```
---


### Working with results

To use the object loop through it, a typical example:

```php
$rows = $db->rows("SELECT * FROM users");
foreach ($rows as $row) {
    echo "<p>$row->name $row->email</p>";
}
```



&nbsp;

## C - Creating data
**insert($table, $data)** - inserts new row into table
> Use `insert()` method to insert data. It expects the table name followed by an array of key and values to insert in to the database.

```php
$data = [
    'name' => 'John Doe',
    'email' => 'name@domain.com',
    'sex' => 'm',
    'role' => 1
];
$db->insert('users', $data);
//This equals to:
$sql = "INSERT INTO users (name, email, sex, role) VALUES (:name, :surname, :sex, :role)";
$pdo->prepare($sql)->execute($data);
```

The insert automatically returns the last inserted id by returning 'lastInsertId' to collect the id:

```php
$id = $db->insert('users', $data);
```

&nbsp;

## U - Updating data
**update($table, $data, $condition)** - updates row(s) in table
> Use `update()` method to update existing row(s) in table. This method expects the table, array of data to update, and either id of updated record or an array containing the where condition.

```php
$data = [
    'email' => 'newname@domain.com',
];
// Using array
$where['id'] = 42;
$db->update('users', $data, $where);
// Using id
$db->update('users', $data, 42);
```
&nbsp;

## D - Deleting data
**delete($table, $condition)** - delete row(s) in table
> Use `delete()` method to delete existing row(s) in table. This method expects the table and either id of deleted record or an array containing the where condition.

```php
// Using array
$where['id'] = 42;
$db->delete('users', $where);
// Using id
$db->delete('users', 42);
```

&nbsp;

## CRUD example
```php
require 'PDOsugar.php';

// Make database connection
$options = [ ... ];
$db = new Database($options);

// Create new user
$data = [
    'name' => 'John Doe',
    'email' => 'name@domain.com',
    'role' => 1
];
$db->insert('users', $data);

// Read all users with role=1
$rows = $db->rows("SELECT * FROM users WHERE role=:role", ['role' => 1]);
foreach ($rows as $row) {
    echo "<p>$row->name $row->email</p>";
}

// Update user with id=42
$data = [
    'email' => 'newname@domain.com',
];
$db->update('users', $data, 42);

// Delete user with id=42
$db->delete('users', 42);
```
