# Destructr legacy drivers

This folder holds what are called "legacy drivers."
These drivers attempt to extend Destructr support to as many databases as possible,
even in some cases at the expense of functionality.

All these drivers are designed to support the following features:
* Inserting objects (and disallowing duplicates at the *SQL* level)
* Updating existing objects
* Deleting objects
* Searching by exact text matches

The things many of this sort of driver will *not* ever support:
* Performance optimization via virtual columns. Many of them will have their
  performance lightly optimized for common system queries, just by hard-coding
  virtual columns for `dso.id`, `dso.deleted`, and `dso.type`
* Complex queries, like joins, REGEX, or LIKE queries.

## Current state of legacy drivers

### SQLite 3

**\Destructr\LegacyDrivers\SQLiteDriver**

**Overall support level: Highly functional, a touch slow**

Via LegacyDrivers\SQLiteDriver Destructr now has surprisingly good support for
SQLite 3. It accomplishes this by using SQLite's user-defined functions feature
to offload JSON parsing to PHP. This means all the JSON parsing is actually up
to spec, storage doesn't need to be flattened like the other legacy drivers,
and tables are dramatically more easily forward-compatible.

If you're considering using legacy MySQL, you should really seriously consider
just using SQLite instead. In benchmarks with 1000 records SQLite's performance
is actually BETTER than MySQL 5.6 for everything but insert/update operations.
In some cases it appears to even be *significantly* faster while also having the
distinct advantage of not using any goofy home-rolled JSON extraction funcitons.

So unless you have a good reason to use MySQL 5.6, you're probably best off
using SQLite if you don't have access to a fully supported database version.

### MySQL 5.6

** No longer supported. You should really use SQLite if you don't have access to
something better. Will most likely never be supported. **
