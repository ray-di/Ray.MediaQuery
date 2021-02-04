<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayAccess;
use Countable;
use Ray\AuraSqlModule\Pagerfanta\AuraSqlPagerInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

/**
 * @extends ArrayAccess<int, Page>
 */
interface PagesInterface extends ArrayAccess, Countable
{
}
