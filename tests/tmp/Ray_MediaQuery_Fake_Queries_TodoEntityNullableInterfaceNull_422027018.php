<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
class TodoEntityNullableInterfaceNull_422027018 extends \Ray\MediaQuery\Fake\Queries\TodoEntityNullableInterfaceNull implements \Ray\MediaQuery\Fake\Queries\TodoEntityNullableInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_item')]
    public function getItem(string $id) : ?\Ray\MediaQuery\Entity\Todo
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /**
     * @return array<Todo>
     */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    public function getList() : ?array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
