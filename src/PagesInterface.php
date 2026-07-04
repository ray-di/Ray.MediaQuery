<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayAccess;
use Countable;

/** @extends ArrayAccess<int, mixed> */
interface PagesInterface extends ArrayAccess, Countable
{
    /**
     * Returns the total number of pages (ceil of result count / perPage).
     */
    public function getNbPages(): int;
}
