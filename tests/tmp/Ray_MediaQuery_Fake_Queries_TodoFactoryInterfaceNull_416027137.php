<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Factory\TodoEntityFactory;
use Ray\MediaQuery\Factory\TodoInjectionFactory;
class TodoFactoryInterfaceNull_416027137 extends \Ray\MediaQuery\Fake\Queries\TodoFactoryInterfaceNull implements \Ray\MediaQuery\Fake\Queries\TodoFactoryInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_item', factory: 'Ray\\MediaQuery\\Factory\\TodoEntityFactory')]
    public function getItem(string $id) : \Ray\MediaQuery\Entity\TodoConstruct
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /**
     * @return array<TodoConstruct>
     */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list', factory: 'Ray\\MediaQuery\\Factory\\TodoEntityFactory')]
    public function getList() : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /**
     * @return array<TodoConstruct>
     */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list', factory: 'Ray\\MediaQuery\\Factory\\TodoInjectionFactory')]
    public function getListInjection() : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
