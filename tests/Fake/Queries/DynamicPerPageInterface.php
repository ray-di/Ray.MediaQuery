<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\FakeBool;
use Ray\MediaQuery\FakeString;
use Ray\MediaQuery\Pages;

interface DynamicPerPageInterface
{
    /**
     * @DbQuery("todo_list")
     * @Pager(perPage="perPage", template="/{?page}")
     */
    #[DbQuery('todo_list'), Pager(perPage: 'perPage', template: '/{?page}')]
    public function get(int $perPage): Pages;

    /**
     * @DbQuery("todo_list_scalar_param")
     * @Pager(perPage="perPage", template="/{?page}")
     */
    #[DbQuery('todo_list_scalar_param'), Pager(perPage: 'perPage', template: '/{?page}')]
    public function getWithScalarParam(int $perPage, int $scalar = 1): Pages;

    /**
     * @DbQuery("todo_list_fake_string_param")
     * @Pager(perPage="perPage", template="/{?page}")
     */
    #[DbQuery('todo_list_fake_string_param'), Pager(perPage: 'perPage', template: '/{?page}')]
    public function getWithFakeStringParam(int $perPage, ?FakeString $fakeString = null): Pages;

    /**
     * @DbQuery("todo_list_fake_bool_param")
     * @Pager(perPage="perPage", template="/{?page}")
     */
    #[DbQuery('todo_list_fake_bool_param'), Pager(perPage: 'perPage', template: '/{?page}')]
    public function getWithFakeBoolParam(int $perPage, ?FakeBool $fakeBool = null): Pages;
}
