<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Result;

/**
 * Return-type contract for DML results built from post-execution PDO state.
 *
 * Any class implementing this interface can be declared as the return type of
 * a `#[DbQuery]` method. The DbQueryInterceptor detects the interface via
 * `is_subclass_of` and calls the static {@see self::postQuery()} factory with
 * a {@see PostQueryContext} holding the executed statement, its connection,
 * and the resolved parameter values. Each result class owns the logic that
 * turns that context into its own shape.
 */
interface PostQueryInterface
{
    public static function postQuery(PostQueryContext $context): static;
}
