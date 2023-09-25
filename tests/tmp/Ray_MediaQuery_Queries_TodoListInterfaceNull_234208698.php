<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;
class TodoListInterfaceNull_234208698 extends \Ray\MediaQuery\Queries\TodoListInterfaceNull implements \Ray\MediaQuery\Queries\TodoListInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: 10, template: '/{?page}')]
    public function __invoke() : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
