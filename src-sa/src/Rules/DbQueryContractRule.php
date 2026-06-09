<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\PagesInterface;
use Ray\MediaQuery\PHPStan\Support\DbQueryMetadata;
use Ray\MediaQuery\PHPStan\Support\SqlFileResolver;
use Ray\MediaQuery\Result\PostQueryInterface;

use function file_get_contents;
use function is_string;
use function sprintf;
use function trim;

/** @implements Rule<ClassMethod> */
final class DbQueryContractRule implements Rule
{
    private SqlFileResolver $sqlFileResolver;

    /** @param list<string> $sqlDirectories */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        array $sqlDirectories,
        private readonly string $factoryMethod = 'factory',
    ) {
        $this->sqlFileResolver = new SqlFileResolver($sqlDirectories);
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $dbQuery = $this->dbQueryMetadata($node, $scope);
        if (! $dbQuery instanceof DbQueryMetadata) {
            return [];
        }

        $errors = [];
        $sqlFile = $this->sqlFileResolver->resolve($dbQuery->id);
        if ($sqlFile === null) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Ray.MediaQuery SQL file for query "%s" was not found in configured sqlDirectories.',
                $dbQuery->id,
            ))->identifier('rayMediaQuery.sqlFileNotFound')->build();
        } else {
            $contents = file_get_contents($sqlFile);
            if (is_string($contents) && trim($contents, " \t\n\r\0\x0B;") === '') {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Ray.MediaQuery SQL file for query "%s" is empty.',
                    $dbQuery->id,
                ))->identifier('rayMediaQuery.emptySqlFile')->build();
            }
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return $errors;
        }

        $methodReflection = $classReflection->getNativeMethod($node->name->toString());
        $returnType = $methodReflection->getVariants()[0]->getReturnType();
        $returnsPages = (new ObjectType(PagesInterface::class))->isSuperTypeOf($returnType)->yes();
        $returnsPostQuery = (new ObjectType(PostQueryInterface::class))->isSuperTypeOf($returnType)->yes();
        $hasPager = $this->hasAttribute($node, $scope, Pager::class);

        if ($hasPager && ! $returnsPages) {
            $errors[] = RuleErrorBuilder::message(
                'Ray.MediaQuery method with #[Pager] must return PagesInterface or an implementation.',
            )->identifier('rayMediaQuery.pagerReturnMismatch')->build();
        }

        if (! $hasPager && $returnsPages && ! $returnsPostQuery) {
            $errors[] = RuleErrorBuilder::message(
                'Ray.MediaQuery method returning PagesInterface or an implementation must declare #[Pager].',
            )->identifier('rayMediaQuery.missingPagerAttribute')->build();
        }

        if ($dbQuery->factory !== '') {
            $this->validateFactory($dbQuery, $errors);
        }

        return $errors;
    }

    private function dbQueryMetadata(ClassMethod $node, Scope $scope): DbQueryMetadata|null
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->attributeName($attribute, $scope) !== DbQuery::class) {
                    continue;
                }

                $id = $this->attributeStringArgument($attribute, 0, 'id', $scope) ?? '';
                if ($id === '') {
                    return null;
                }

                $factory = $this->attributeStringArgument($attribute, 2, 'factory', $scope) ?? '';

                return new DbQueryMetadata($id, $factory);
            }
        }

        return null;
    }

    private function hasAttribute(ClassMethod $node, Scope $scope, string $attributeClass): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if ($this->attributeName($attribute, $scope) === $attributeClass) {
                    return true;
                }
            }
        }

        return false;
    }

    private function attributeName(Attribute $attribute, Scope $scope): string
    {
        return $scope->resolveName($attribute->name);
    }

    private function attributeStringArgument(Attribute $attribute, int $position, string $name, Scope $scope): string|null
    {
        foreach ($attribute->args as $index => $arg) {
            if ($arg->name instanceof Identifier && $arg->name->toString() !== $name) {
                continue;
            }

            if ($arg->name === null && $index !== $position) {
                continue;
            }

            return $this->stringValue($arg->value, $scope);
        }

        return null;
    }

    private function stringValue(Expr $expr, Scope $scope): string|null
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof ClassConstFetch && $expr->name instanceof Identifier && $expr->name->toString() === 'class') {
            if ($expr->class instanceof Name) {
                return $this->resolveName($expr->class, $scope);
            }
        }

        return null;
    }

    private function resolveName(Name $name, Scope $scope): string
    {
        return $scope->resolveName($name);
    }

    /** @param list<IdentifierRuleError> $errors */
    private function validateFactory(DbQueryMetadata $dbQuery, array &$errors): void
    {
        if (! $this->reflectionProvider->hasClass($dbQuery->factory)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Ray.MediaQuery factory class "%s" for query "%s" was not found.',
                $dbQuery->factory,
                $dbQuery->id,
            ))->identifier('rayMediaQuery.invalidFactory')->build();

            return;
        }

        $classReflection = $this->reflectionProvider->getClass($dbQuery->factory);
        if ($classReflection->hasMethod($this->factoryMethod)) {
            return;
        }

        $errors[] = RuleErrorBuilder::message(sprintf(
            'Ray.MediaQuery factory class "%s" for query "%s" must define method "%s()".',
            $dbQuery->factory,
            $dbQuery->id,
            $this->factoryMethod,
        ))->identifier('rayMediaQuery.invalidFactory')->build();
    }
}
