<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
class PromiseItemInterfaceNull_2856756089 extends \Ray\MediaQuery\Queries\PromiseItemInterfaceNull implements \Ray\MediaQuery\Queries\PromiseItemInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('promise_item', type: 'row')]
    public function get(string $id) : array
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
