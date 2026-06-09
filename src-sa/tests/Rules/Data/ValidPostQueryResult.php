<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Rules\Data;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class ValidPostQueryResult implements PostQueryInterface
{
    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        unset($context);

        return new static();
    }
}
