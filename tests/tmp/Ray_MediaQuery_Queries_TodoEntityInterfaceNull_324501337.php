<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoMemo;
class TodoEntityInterfaceNull_324501337 extends \Ray\MediaQuery\Queries\TodoEntityInterfaceNull implements \Ray\MediaQuery\Queries\TodoEntityInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_item')]
    public function getItem(string $id) : \Ray\MediaQuery\Entity\Todo
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /**
     * @return array<Todo>
     */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    public function getList() : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /** @return array<TodoMemo> */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list_join')]
    public function getListWithMemo(string $id) : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
