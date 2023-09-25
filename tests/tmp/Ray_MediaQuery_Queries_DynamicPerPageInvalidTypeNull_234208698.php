<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;
class DynamicPerPageInvalidTypeNull_234208698 extends \Ray\MediaQuery\Queries\DynamicPerPageInvalidTypeNull implements \Ray\MediaQuery\Queries\DynamicPerPageInvalidType, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: 'perPage', template: '/{?page}')]
    public function __invoke($perPage) : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
