<?php

declare(strict_types=1);

namespace Ray\MediaQuery\PHPStan\Tests\Fixture\Data;

use Ray\MediaQuery\Annotation\DbQuery;

interface SqlFilesInterface
{
    #[DbQuery('existing_query')]
    public function existing(): array;

    #[DbQuery('missing_query')]
    public function missing(): array;

    #[DbQuery('empty_query')]
    public function emptyFile(): array;
}
