<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules\Data;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface ValidQueryInterface
{
    #[DbQuery('valid')]
    public function list(): array;

    #[DbQuery('valid', factory: ValidInstanceFactory::class)]
    public function withInstanceFactory(): array;

    #[DbQuery('valid', factory: ValidStaticFactory::class)]
    public function withStaticFactory(): array;

    /** @return Pages<object> */
    #[DbQuery('valid')]
    #[Pager(perPage: 10)]
    public function pages(): Pages;

    #[DbQuery('valid')]
    public function postQuery(): ValidPostQueryResult;
}
