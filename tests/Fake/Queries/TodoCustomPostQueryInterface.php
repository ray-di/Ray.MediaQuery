<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\RowCountWithQuery;

interface TodoCustomPostQueryInterface
{
    #[DbQuery('todo_delete')]
    public function delete(string $id): RowCountWithQuery;
}
