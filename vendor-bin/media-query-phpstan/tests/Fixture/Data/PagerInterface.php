<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Fixture\Data;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface PagerInterface
{
    #[DbQuery('existing_query'), Pager]
    public function pagerWithArray(): array;

    #[DbQuery('existing_query')]
    public function pagesWithoutPager(): Pages;

    #[DbQuery('existing_query'), Pager]
    public function pagerWithPages(): Pages;

    #[DbQuery('existing_query')]
    public function plainArray(): array;
}
