<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
class PromiseListInterfaceNull_2856756089 extends \Ray\MediaQuery\Queries\PromiseListInterfaceNull implements \Ray\MediaQuery\Queries\PromiseListInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('promise_list')]
    public function get() : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
