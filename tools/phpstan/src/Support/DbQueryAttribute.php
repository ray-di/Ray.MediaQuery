<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Support;

use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;

use function count;

/**
 * Resolved, constant view of a `#[DbQuery]` attribute.
 *
 * Mirrors the constructor signature
 * `DbQuery(string $id, string $type = 'row_list', string $factory = '')`.
 * Values that are not statically-constant strings are left null so the rule can
 * skip them rather than report a false positive.
 */
final class DbQueryAttribute
{
    /** @var list<string> Constructor parameter order of Ray\MediaQuery\Annotation\DbQuery. */
    private const PARAMETER_ORDER = ['id', 'type', 'factory'];

    public function __construct(
        public readonly string|null $id,
        public readonly string|null $type,
        public readonly string|null $factory,
    ) {
    }

    public static function fromNode(Attribute $attr, Scope $scope): self
    {
        $values = [];
        $position = 0;
        foreach ($attr->args as $arg) {
            if ($arg->name !== null) {
                $name = $arg->name->toString();
            } else {
                $name = self::PARAMETER_ORDER[$position] ?? null;
                $position++;
            }

            if ($name === null) {
                continue;
            }

            $constantStrings = $scope->getType($arg->value)->getConstantStrings();
            if (count($constantStrings) === 1) {
                $values[$name] = $constantStrings[0]->getValue();
            }
        }

        return new self(
            $values['id'] ?? null,
            $values['type'] ?? null,
            $values['factory'] ?? null,
        );
    }
}
