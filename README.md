# Destructr

[![PHPUnit Tests](https://github.com/jobyone/destructr/actions/workflows/test.yml/badge.svg)](https://github.com/jobyone/destructr/actions/workflows/test.yml)

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
// but for common databases DriverFactory::factory should pick the right class
$driver = \Destructr\DriverFactory::factory(
  'mysql:host=127.0.0.1',
  'username',
  'password'
);
// Driver is then used to construct a Factory
$factory = new \Destructr\Factory(
  $driver,      //driver is used to manage connection and generate queries
  'dso_objects' //all of a Factory's data is stored in a single table
);
```

### Creating a new record

Next, you can use the factory to create a new record object.

```php
// by default all objects are the DSO class, but factories can be made to use
// other classes depending on what data objects are instantiated with
$obj = $factory->create();

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

### Searching

Factories provide an interface for creating `Search` objects, which allow you to enter in various SQL clauses in a structured and abstract fashion.

```php
// get a new search object from the factory
$search = $factory->search();

// Search::where() takes SQL for the WHERE clause of a query
// ${path} syntax is used to reference data within objects, and
// works everywhere in searches
$search->where('${dso.date.modified} > :time');

// Search::order() takes SQL to go inside an ORDER BY clause
// in the final query.
$search->order('${dso.date.modified} desc');

// Search limit/offset methods can be used for pagination
// there is also a paginate() method for more conveniently
// paginating results
$search->paginate(20,1);

// Search::execute() returns an array of the resulting objects
$results = $search->execute();
```

## Requirements

This system relies **heavily** on the JSON features of the underlying database.
This means it cannot possibly run without a database that supports JSON features.
Basically if a database doesn't have JSON functions it's probably impossible for Destructr to ever work with it.

At the moment there is pretty decent support for:

* MySQL >=5.7.8
* MariaDB >=10.2.7
* SQLite 3 (with some caveats)

In practice this means Destructr will **never** be able to run on less than the following versions of the following popular databases:

* MySQL >=5.7.8
* MariaDB >=10.2.7
* PostgreSQL >=9.3
* SQL Server >=2016

Theoretically Destructr is also an excellent fit for NoSQL databases.
If I ever find myself needing it there's a good chance it's possible to write drivers for running it on something like MongoDB as well.
It might even be kind of easy.

### SQLite caveats

MySQL and MariaDB drivers set virtual columns to be generated automatically using their native JSON functions.
SQLite doesn't have native JSON (in most environments, at least), so Destructr itself manually updates virtual columns whenever objects are inserted or updated.
In practice this won't matter *if* you are doing all your insertion and updating via Destructr.
If you're doing updates to your database via any other method, however, you need to be aware of this, and manually update the virtual column values.
