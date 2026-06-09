<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules;

use Override;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Ray\MediaQuery\PHPStan\Rules\DbQueryContractRule;

/** @extends RuleTestCase<DbQueryContractRule> */
final class DbQueryContractRuleTest extends RuleTestCase
{
    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-rule-tests.neon'];
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new DbQueryContractRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            [__DIR__ . '/Data/sql'],
        );
    }

    public function testValidQueries(): void
    {
        $this->analyse([__DIR__ . '/Data/valid.php'], []);
    }

    public function testInvalidQueries(): void
    {
        $this->analyse([__DIR__ . '/Data/invalid.php'], [
            [
                'Ray.MediaQuery SQL file for query "missing" was not found in configured sqlDirectories.',
                13,
            ],
            [
                'Ray.MediaQuery SQL file for query "empty" is empty.',
                19,
            ],
            [
                'Ray.MediaQuery method with #[Pager] must return PagesInterface or an implementation.',
                25,
            ],
            [
                'Ray.MediaQuery method returning PagesInterface or an implementation must declare #[Pager].',
                32,
            ],
            [
                'Ray.MediaQuery factory class "Ray\\MediaQuery\\PHPStan\\Tests\\Rules\\Data\\MissingFactory" for query "valid" was not found.',
                38,
            ],
            [
                'Ray.MediaQuery factory class "Ray\\MediaQuery\\PHPStan\\Tests\\Rules\\Data\\FactoryWithoutFactoryMethod" for query "valid" must define method "factory()".',
                44,
            ],
        ]);
    }
}
