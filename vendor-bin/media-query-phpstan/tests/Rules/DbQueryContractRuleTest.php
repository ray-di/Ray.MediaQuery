<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Ray\MediaQuery\PHPStan\Rules\DbQueryContractRule;
use Ray\MediaQuery\PHPStan\Support\SqlFileResolver;

/** @extends RuleTestCase<DbQueryContractRule> */
final class DbQueryContractRuleTest extends RuleTestCase
{
    private const SQL_DIR = __DIR__ . '/../Fixture/sql';

    protected function getRule(): Rule
    {
        return new DbQueryContractRule(
            new SqlFileResolver([self::SQL_DIR]),
            $this->createReflectionProvider(),
            'factory',
        );
    }

    public function testSqlFileExistence(): void
    {
        $this->analyse([__DIR__ . '/../Fixture/Data/SqlFilesInterface.php'], [
            [
                'SQL file "missing_query.sql" for #[DbQuery(\'missing_query\')] was not found in configured sqlDirectories (' . self::SQL_DIR . ').',
                14,
            ],
            [
                'SQL file "' . self::SQL_DIR . '/empty_query.sql" for #[DbQuery(\'empty_query\')] is empty.',
                17,
            ],
        ]);
    }

    public function testPagerCoherence(): void
    {
        $this->analyse([__DIR__ . '/../Fixture/Data/PagerInterface.php'], [
            [
                '#[Pager] requires the return type to be Ray\MediaQuery\PagesInterface, but array is declared.',
                13,
            ],
            [
                'Return type Ray\MediaQuery\Pages requires a #[Pager] attribute.',
                16,
            ],
        ]);
    }

    public function testFactoryExistence(): void
    {
        $this->analyse([__DIR__ . '/../Fixture/Data/FactoryInterface.php'], [
            [
                'Factory class Ray\MediaQuery\PHPStan\Tests\Fixture\Data\MissingFactory declared in #[DbQuery] does not exist.',
                14,
            ],
            [
                'Factory method Ray\MediaQuery\PHPStan\Tests\Fixture\Data\FactoryWithoutFactoryMethod::factory() declared in #[DbQuery] does not exist.',
                17,
            ],
        ]);
    }
}
