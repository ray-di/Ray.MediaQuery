<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
class TodoItemInterfaceNull_234208698 extends \Ray\MediaQuery\Queries\TodoItemInterfaceNull implements \Ray\MediaQuery\Queries\TodoItemInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_item', type: 'row')]
    public function __invoke(string $id) : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
