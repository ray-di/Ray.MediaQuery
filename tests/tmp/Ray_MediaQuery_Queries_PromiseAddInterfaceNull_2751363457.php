<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use DateTimeInterface;
use Ray\MediaQuery\Annotation\DbQuery;
class PromiseAddInterfaceNull_2751363457 extends \Ray\MediaQuery\Queries\PromiseAddInterfaceNull implements \Ray\MediaQuery\Queries\PromiseAddInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('promise_add')]
    public function add(string $id, string $title, \DateTimeInterface $time = null) : void
    {
        $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
