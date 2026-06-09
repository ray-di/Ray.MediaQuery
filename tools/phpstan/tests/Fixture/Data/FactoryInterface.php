<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Fixture\Data;

use Ray\MediaQuery\Annotation\DbQuery;

interface FactoryInterface
{
    #[DbQuery('existing_query', factory: ValidFactory::class)]
    public function valid(): object;

    #[DbQuery('existing_query', factory: 'Ray\MediaQuery\PHPStan\Tests\Fixture\Data\MissingFactory')]
    public function missingClass(): object;

    #[DbQuery('existing_query', factory: FactoryWithoutFactoryMethod::class)]
    public function missingMethod(): object;
}
