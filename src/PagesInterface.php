<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use ArrayAccess;
use Countable;

/**
 * @extends ArrayAccess<int, mixed>
 */
interface PagesInterface extends ArrayAccess, Countable
{
}
