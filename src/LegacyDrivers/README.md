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

**\Digraph\Destructr\LegacyDrivers\SQLiteDriver**

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

**\Digraph\Destructr\LegacyDrivers\MySQL56Driver**

**Overall support level: Decent performance, highly suspect accuracy**

LegacyDrivers\MySQL56Driver provides bare-minimum support for MySQL < 5.7.
This driver now passes the basic tests and basic integration tests, but hasn't
been verified in the slightest beyond that.

It flattens unstructured JSON and uses a highly dodgy user-defined function to
extract values from it. There are absolutely edge cases that will extract the
wrong data. That said, outside of those edge cases it should actually work
fairly well. All the sorting and filtering is happening in SQL, and things
should mostly be fairly predictable.

This driver should be your last resort. I cannot emphasize enough that this
thing is extremely kludgey and should not be trusted.
