<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\PagesInterface;
use Ray\MediaQuery\PHPStan\Support\AttributeFinder;
use Ray\MediaQuery\PHPStan\Support\DbQueryAttribute;
use Ray\MediaQuery\PHPStan\Support\SqlFileResolver;

use function array_merge;
use function file_get_contents;
use function sprintf;
use function trim;

/**
 * Statically verifies the cross-artifact contract of `#[DbQuery]` methods that
 * PHPStan core cannot see (SQL files, Pager coherence, factory existence).
 *
 * MVP scope — checks chosen for near-zero false positives:
 *  - the `{id}.sql` file exists in a configured SQL directory and is not empty;
 *  - `#[Pager]` is present iff the return type is a {@see PagesInterface};
 *  - a declared `factory:` class exists and exposes the configured method.
 *
 * Invalid `type:` literals are intentionally not checked here — PHPStan core
 * already reports them via the `'row'|'row_list'` constructor type.
 *
 * @implements Rule<InClassMethodNode>
 */
final class DbQueryContractRule implements Rule
{
    public function __construct(
        private SqlFileResolver $sqlFileResolver,
        private ReflectionProvider $reflectionProvider,
        private string $factoryMethod,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $classMethod = $node->getOriginalNode();
        $dbQueryNode = AttributeFinder::find($classMethod, DbQuery::class);
        if ($dbQueryNode === null) {
            return [];
        }

        $dbQuery = DbQueryAttribute::fromNode($dbQueryNode, $scope);
        $returnType = $node->getMethodReflection()->getReturnType();
        $hasPager = AttributeFinder::has($classMethod, Pager::class);

        return array_merge(
            $this->checkSqlFile($dbQuery),
            $this->checkPager($hasPager, $returnType),
            $this->checkFactory($dbQuery),
        );
    }

    /** @return list<IdentifierRuleError> */
    private function checkSqlFile(DbQueryAttribute $dbQuery): array
    {
        if ($dbQuery->id === null || ! $this->sqlFileResolver->isConfigured()) {
            return [];
        }

        $path = $this->sqlFileResolver->resolve($dbQuery->id);
        if ($path === null) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'SQL file "%s.sql" for #[DbQuery(\'%s\')] was not found in configured sqlDirectories (%s).',
                    $dbQuery->id,
                    $dbQuery->id,
                    $this->sqlFileResolver->describeDirectories(),
                ))->identifier('rayMediaQuery.sqlFileNotFound')->build(),
            ];
        }

        if (trim((string) file_get_contents($path)) === '') {
            return [
                RuleErrorBuilder::message(sprintf(
                    'SQL file "%s" for #[DbQuery(\'%s\')] is empty.',
                    $path,
                    $dbQuery->id,
                ))->identifier('rayMediaQuery.emptySqlFile')->build(),
            ];
        }

        return [];
    }

    /** @return list<IdentifierRuleError> */
    private function checkPager(bool $hasPager, Type $returnType): array
    {
        $pagesType = new ObjectType(PagesInterface::class);
        $isPages = $pagesType->isSuperTypeOf($returnType);

        if ($hasPager && $isPages->no()) {
            return [
                RuleErrorBuilder::message(sprintf(
                    '#[Pager] requires the return type to be %s, but %s is declared.',
                    PagesInterface::class,
                    $returnType->describe(VerbosityLevel::typeOnly()),
                ))->identifier('rayMediaQuery.pagerReturnMismatch')->build(),
            ];
        }

        if (! $hasPager && $isPages->yes()) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Return type %s requires a #[Pager] attribute.',
                    $returnType->describe(VerbosityLevel::typeOnly()),
                ))->identifier('rayMediaQuery.missingPagerAttribute')->build(),
            ];
        }

        return [];
    }

    /** @return list<IdentifierRuleError> */
    private function checkFactory(DbQueryAttribute $dbQuery): array
    {
        $factory = $dbQuery->factory;
        if ($factory === null || $factory === '') {
            return [];
        }

        if (! $this->reflectionProvider->hasClass($factory)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Factory class %s declared in #[DbQuery] does not exist.',
                    $factory,
                ))->identifier('rayMediaQuery.invalidFactory')->build(),
            ];
        }

        if (! $this->reflectionProvider->getClass($factory)->hasMethod($this->factoryMethod)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Factory method %s::%s() declared in #[DbQuery] does not exist.',
                    $factory,
                    $this->factoryMethod,
                ))->identifier('rayMediaQuery.invalidFactory')->build(),
            ];
        }

        return [];
    }
}
