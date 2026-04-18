<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

interface TodoAffectedInterface
{
    #[DbQuery('todo_delete')]
    public function delete(string $id): AffectedRows;

    #[DbQuery('todo_update')]
    public function update(string $id, string $title): AffectedRows;

    #[DbQuery('counter_add')]
    public function addCounter(string $label): AffectedRows;
}
