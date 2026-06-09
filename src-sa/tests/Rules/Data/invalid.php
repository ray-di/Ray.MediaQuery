<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules\Data;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface MissingSqlQueryInterface
{
    #[DbQuery('missing')]
    public function list(): array;
}

interface EmptySqlQueryInterface
{
    #[DbQuery('empty')]
    public function list(): array;
}

interface PagerReturnMismatchQueryInterface
{
    #[DbQuery('valid')]
    #[Pager(perPage: 10)]
    public function list(): array;
}

interface MissingPagerQueryInterface
{
    #[DbQuery('valid')]
    public function pages(): Pages;
}

interface MissingFactoryClassQueryInterface
{
    #[DbQuery('valid', factory: 'Ray\\MediaQuery\\PHPStan\\Tests\\Rules\\Data\\MissingFactory')]
    public function list(): array;
}

interface MissingFactoryMethodQueryInterface
{
    #[DbQuery('valid', factory: FactoryWithoutFactoryMethod::class)]
    public function list(): array;
}
