<?php

declare(strict_types=1);

namespace Ray\MediaQuery\DbQuery;

use ArrayAccess;
use Countable;

/**
 * @extends ArrayAccess<int, mixed>
 */
interface PagesInterface extends ArrayAccess, Countable
{
}
