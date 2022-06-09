<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Exception;

/**
 * The argument name specified in PerPage does not exist in the arguments of that method
 */
class InvalidPerPageVarNameException extends LogicException
{
}
