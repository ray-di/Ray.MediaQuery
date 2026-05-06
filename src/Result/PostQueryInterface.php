<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

/**
 * Return-type contract for results built after a query executes.
 *
 * Any class implementing this interface can be declared as the return type of
 * a `#[DbQuery]` method, for either a DML statement (e.g. {@see AffectedRows},
 * {@see InsertedRow}) or a SELECT (a typed collection wrapping the hydrated
 * rows). The DbQueryInterceptor detects the interface via `is_subclass_of` and
 * calls the static {@see self::fromContext()} factory with a
 * {@see PostQueryContext} holding the executed statement, its connection, the
 * resolved parameter values, and — for SELECT — the hydrated rows. Each result
 * class owns the logic that turns that context into its own shape.
 */
interface PostQueryInterface
{
    public static function fromContext(PostQueryContext $context): static;
}
