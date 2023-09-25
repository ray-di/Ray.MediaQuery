<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\TodoConstruct;
class TodoConstcuctEntityInterfaceNull_422027018 extends \Ray\MediaQuery\Queries\TodoConstcuctEntityInterfaceNull implements \Ray\MediaQuery\Queries\TodoConstcuctEntityInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_item')]
    public function getItem(string $id) : \Ray\MediaQuery\Entity\TodoConstruct
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /**
     * @return array<TodoConstruct>
     */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    public function getList() : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
