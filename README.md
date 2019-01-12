# Destructr

Destructr is a specialized ORM that allows a seamless mix of structured, relational data with unstructured JSON data.

## Getting started

The purpose of Destructr is to allow many "types" of objects to be stored in a single table.
Every Destructr Data Object (DSO) simply contains an array of data that will be saved into the database as JSON.
Array access is also flattened, using dots as delimiters, so rather than reading `$dso["foo"]["bar"]` you access that data via `$dso["foo.bar"]`.
This is for two reasons.
It sidesteps the issue of updating nested array values by reference, and it creates an unambiguous way of locating a node of the unstructured data with a string, which mirrors how we can write SQL queries to reference them.

If this sounds like an insanely slow idea, that's because it is.
Luckily MySQL and MariaDB have mechanisms we can take advantage of to make generated columns from any part of the unstructured data, so that pieces of it can be pulled out into their own virtual columns for indexing and faster searching/sorting.

### Database driver and factory

In order to read/write objects from a database table, you'll need to configure a Driver and Factory class.

```php
// DriverFactory::factory() has the same arguments as PDO::__construct
// You can also construct a driver directly, from a class in Drivers,
// but for common databases DriverFactory::factory should pick the right class.
// Note: It's important to specify a character set, because malformed characters
// completely break PHP's json_decode() function.
$driver = \Destructr\DriverFactory::factory(
  'mysql:host=127.0.0.1;charset=utf8',
  'username',
  'password'
);
// Driver is then used to construct a Factory
$factory = new \Destructr\Factory(
  // a Driver is used to manage connection and generate queries
  $driver,
  // all of a Factory's data is stored in a single table, to split data between
  // multiple tables, you'll need to create multiple Factories. Drivers can be
  // reused across multiple Factories, and only a single connection will be
  // used, if supported by the database.
  'dso_objects'
);
```

### Creating a new record

Next, you can use the factory to create a new record object.

```php
// by default all objects are the DSO class, but factories can be made to use
// other classes depending on what data objects are instantiated with.
// create() can be optionally given an array of initial data.
$obj = $factory->create(['foo'=>'bar');

// once created, an object can be worked with like a SelfReferencingFlatArray
// see https://gitlab.com/byjoby/flatrr for more information about that
$obj['bar.baz'] = 'buzz';

// returns boolean indicating whether insertion succeeded
// insert() must be called before update() will work
$obj->insert();

// set a new value and call update() to save changes into database. update()
// will return true without doing anything if no changes have been made.
$obj['foo.bar'] = 'some value';
$obj->update();

// deleting an object will by default just set dso.deleted to the current time
// objects with a non-null dso.deleted are excluded from queries by default
// delete() calls update() inside it, so its effect is immediate
$obj->delete();

// objects that were deleted via default delete() are recoverable via undelete()
// undelete() also calls update() for you
$obj->undelete();

// objects can be actually removed from the table by calling delete(true)
$obj->delete(true);
```

## Requirements

### Suggested databases

Currently, Destructr is best supported by MySQL 5.7, SQLite 3, and MariaDB >=10.2.

If you only have access to a MySQL 5.6 server there is a driver available, but it's buggy and not as well tested.
Given the choice, you should almost always choose SQLite over MySQL <=5.6.
The SQLite driver is actually very fast in benchmarks, and performs very close to the same speed as MySQL 5.7 for simple queries or small databases with object counts on the order of a few thousand.
For efficient indexing of non-standard JSON paths or large numbers of objects, MySQL 5.7 or MariaDB >=10.2 is highly recommended.

### Theoretical minimum versions

This system relies **heavily** on the JSON features of the underlying database.
This means it cannot possibly run without a database that supports JSON features.
Exact requirements are in flux during development, but basically if a database doesn't have JSON functions it's probably impossible for Destructr to ever work with it.

In practice this means Destructr will **never** be able to run properly on less than the following versions of the following popular databases:

* MySQL >=5.7
* MariaDB >=10.2
* PostgreSQL >=9.3
* SQL Server >=2016

Theoretically Destructr is also an excellent fit for NoSQL databases.
If I ever find myself needing it there's a good chance it's possible to write drivers for running it on something like MongoDB as well.
It might even be kind of easy.
