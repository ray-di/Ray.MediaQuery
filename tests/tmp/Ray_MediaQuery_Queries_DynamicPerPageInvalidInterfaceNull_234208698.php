<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;
class DynamicPerPageInvalidInterfaceNull_234208698 extends \Ray\MediaQuery\Queries\DynamicPerPageInvalidInterfaceNull implements \Ray\MediaQuery\Queries\DynamicPerPageInvalidInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: '__not_exsits_', template: '/{?page}')]
    public function __invoke(int $num) : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
