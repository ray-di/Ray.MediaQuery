<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\AuraSqlModule\Annotation\Transactional;
use Ray\MediaQuery\Annotation\DbQuery;
class TodoAddInterfaceNull_2249991120 extends \Ray\MediaQuery\Queries\TodoAddInterfaceNull implements \Ray\MediaQuery\Queries\TodoAddInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_add')]
    #[\Ray\AuraSqlModule\Annotation\Transactional]
    public function __invoke(string $id, string $title) : void
    {
        $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
