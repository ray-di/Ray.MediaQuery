---
layout: default
title: Ray.MediaQuery FAQ
description: Frequently asked questions about Ray.MediaQuery, interface-driven SQL, result types, factories, BDR, and architecture.
lang: en
permalink: /faq/
---

# Ray.MediaQuery FAQ

[日本語 (Japanese)]({{ '/faq/ja/' | relative_url }})

## Product

### Q: What is Ray.MediaQuery?

**A: Ray.MediaQuery is interface-driven SQL for PHP.** You define a PHP interface, attach `#[DbQuery]`, write a SQL file, and Ray.MediaQuery provides the implementation through Ray.Di and AOP.

The interface is the contract. The SQL file is the query. The return type declares how the result should be fetched, hydrated, wrapped, or ignored.

### Q: How is this different from an ORM?

**A: Ray.MediaQuery keeps SQL visible instead of hiding it behind object abstractions.** ORMs are useful when the database shape and object model align. They become difficult when a read needs joins, CTEs, window functions, aggregates, calculated fields, or a screen-specific projection.

Ray.MediaQuery lets SQL do that work directly, then gives the result a typed PHP surface.

### Q: Why use an interface?

**A: The interface gives the query a stable PHP boundary without writing an implementation class.** Callers depend on a method signature and return type. Ray.MediaQuery binds that method to SQL at runtime.

This keeps the code explicit: no query builder chain, no generated repository class in your source tree, and no hidden SQL.

### Q: Does Ray.MediaQuery only read data?

**A: No.** It can execute `SELECT`, `INSERT`, `UPDATE`, `DELETE`, and multi-statement SQL files.

Use `void` when the caller does not need a result. Use `AffectedRows` for row counts, `InsertedRow` for insert metadata, or a custom `PostQueryInterface` result when you want to shape the post-query result yourself.

### Q: What result types can methods return?

**A: The return type is the intent declaration.** Common shapes include:

- `array` for associative row lists
- `?array` with `type: 'row'` for one associative row
- `array<User>` via docblock for hydrated entity lists
- `?User` with `type: 'row'` for one hydrated entity
- `void`, `AffectedRows`, or `InsertedRow` for DML
- `Pages<User>` for lazy pagination
- `PostQueryInterface` implementations for custom wrappers

For the full matrix, see the [Manual]({{ '/reference/' | relative_url }}).

## Rich Objects

### Q: What are rich domain objects in Ray.MediaQuery?

**A: They are typed PHP objects created from SQL results, often through factories.** A query can return more than a data bag. It can return an object with computed values, labels, predicates, or service-backed behavior.

This is the old README idea: SQL stays excellent at data access, while PHP expresses types and behavior.

### Q: Can factories use dependency injection?

**A: Yes.** A factory can receive services from the DI container and use them to construct richer objects: tax calculators, rule engines, formatters, clocks, currency converters, providers, and value object builders.

Keep I/O intentional. If a factory calls an external API for every row in a list, you have created an N+1 variant. Use batching, provider-side caching, preloaded data, or a separate detail query when the expensive data is only needed on detail screens.

### Q: Where should business logic live?

**A: Put read-side derivation on the read model, and write-side invariants on the write model.** A BDR object can answer presentation questions such as labels, totals, visibility, or priority for the current projection.

It should not be the only place that decides whether state may change. That decision belongs to the command/write path.

### Q: Is a BDR object a DTO, Entity, or Value Object?

**A: It is a read model for a projection.** It may look like a DTO because it is created from query results, but it can expose behavior. It is not a persistence Entity because it does not track changes or save itself.

When identity matters, it can carry an ID. When equality-by-value matters, it can behave like a value object. The important point is that it represents one query result shape, not the whole lifecycle of a database row.

## Architecture

### Q: How do I save modified objects back to the database?

**A: Use an explicit write path.** A BDR read object is shaped for a screen, report, API response, or use case. It does not save itself.

Read the projection when it helps display the current state or available actions. For a state change, call a command or application write use case. That write path validates write-side invariants and persists the result with `UPDATE`, `INSERT`, `DELETE`, or another mechanism.

### Q: Is BDR CQRS?

**A: BDR fits the Query side of CQRS, but it is more precise to call it a rich read-model pattern.** CQRS is not mainly about putting read repositories and write repositories in different folders, or using separate databases. Its core is that reads and writes often need different models.

BDR makes the read model explicit: SQL defines the projection, and PHP gives it type and behavior. The command/write model remains an application design choice.

### Q: Is separate SQL for each screen a DRY violation?

**A: Not by itself.** If two screens ask different questions, separate SQL is often clearer than forcing one generic query or one reusable Entity model to serve both.

Repeating a column name is cheaper than coupling two use cases that change for different reasons. Extract shared SQL only when the shared part has stable meaning.

### Q: Can I introduce Ray.MediaQuery gradually?

**A: Yes.** Start with one read path that is awkward in the current model: a report, search result, dashboard, or detail screen with derived fields.

Keep existing write code unchanged. Add one interface, one SQL file, and one return type. If the result is clearer, repeat.

### Q: Can I keep using an ORM?

**A: Yes.** Ray.MediaQuery does not require replacing your whole persistence layer. It is common to keep an existing ORM or write model and use Ray.MediaQuery for read paths where explicit SQL is clearer.

The important boundary is conceptual: reads can have their own model instead of being forced through the write model.

## Testing

### Q: How should I test Ray.MediaQuery code?

**A: Test each layer independently.** SQL tests check that the query returns the expected rows and columns. Factory tests check construction and conversion. Domain object tests check behavior without a database.

For application tests, fake the query interface. Because the contract is an interface with typed return values, the fake can be small and focused on the use case.

### Q: Why is this AI-friendly?

**A: The contract is readable.** The interface says what the application asks for. The SQL file says exactly what runs. The return type says what shape comes back.

There is no hidden query generation for an assistant to reverse-engineer.

## Boundaries

### Q: When should I not use Ray.MediaQuery?

**A: Do not add it where the current model is already simple.** A small CRUD screen that maps cleanly to one table may not need a dedicated rich read model.

Ray.MediaQuery is most useful when SQL can express the projection clearly, and PHP types or factories make that projection safer to use.

### Q: When should I use `SqlQueryInterface` directly?

**A: Use it for advanced or dynamic cases where an interface method is not the best boundary.** For normal application use, prefer `#[DbQuery]` interfaces. They keep the contract explicit and easy to fake.

Direct SQL execution is useful for infrastructure code, dynamic query dispatch, or lower-level adapters.
