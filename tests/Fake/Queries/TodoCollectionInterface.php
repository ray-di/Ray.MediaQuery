<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Factory\TodoEntityFactory;
use Ray\MediaQuery\FakeTodoCollection;
use Ray\MediaQuery\FakeUntypedCollection;

interface TodoCollectionInterface
{
    /** Returns FakeTodoCollection containing raw associative arrays. */
    #[DbQuery('todo_list')]
    public function list(): FakeTodoCollection;

    /** Returns FakeTodoCollection containing TodoConstruct instances via static factory. */
    #[DbQuery('todo_list', factory: TodoEntityFactory::class)]
    public function hydrated(): FakeTodoCollection;

    /**
     * Returns FakeTodoCollection containing Todo instances via docblock entity.
     *
     * @return FakeTodoCollection<Todo>
     */
    #[DbQuery('todo_list')]
    public function docblockEntity(): FakeTodoCollection;

    /** Returns FakeUntypedCollection (Laravel-style untyped constructor). */
    #[DbQuery('todo_list')]
    public function untyped(): FakeUntypedCollection;
}
