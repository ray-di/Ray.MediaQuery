<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use DateTimeInterface;
use Ray\InputQuery\Attribute\Input;

final class TodoCreateInput
{
    public function __construct(
        #[Input] public readonly string $title,
        #[Input] public readonly UserInput $assignee,
        #[Input] public readonly ?DateTimeInterface $dueDate
    ) {}
}