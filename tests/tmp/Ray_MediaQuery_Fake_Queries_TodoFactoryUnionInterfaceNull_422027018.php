<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Fake\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Todo;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Entity\TodoConstructExtended;
use Ray\MediaQuery\Factory\TodoEntityFactory;
class TodoFactoryUnionInterfaceNull_422027018 extends \Ray\MediaQuery\Fake\Queries\TodoFactoryUnionInterfaceNull implements \Ray\MediaQuery\Fake\Queries\TodoFactoryUnionInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_item', factory: 'Ray\\MediaQuery\\Factory\\TodoEntityFactory')]
    public function getItem(string $id) : \Ray\MediaQuery\Entity\TodoConstruct|\Ray\MediaQuery\Entity\TodoConstructExtended
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    /**
     * @return array<TodoConstruct|TodoConstructExtended>
     */
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list', factory: 'Ray\\MediaQuery\\Factory\\TodoEntityFactory')]
    public function getList() : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
