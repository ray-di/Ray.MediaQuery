<?php

declare (strict_types=1);
namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\FakeBool;
use Ray\MediaQuery\FakeString;
use Ray\MediaQuery\Pages;
class DynamicPerPageInterfaceNull_1563213685 extends \Ray\MediaQuery\Queries\DynamicPerPageInterfaceNull implements \Ray\MediaQuery\Queries\DynamicPerPageInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: 'perPage', template: '/{?page}')]
    public function get(int $perPage) : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list_scalar_param')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: 'perPage', template: '/{?page}')]
    public function getWithScalarParam(int $perPage, int $scalar = 1) : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list_fake_string_param')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: 'perPage', template: '/{?page}')]
    public function getWithFakeStringParam(int $perPage, ?\Ray\MediaQuery\FakeString $fakeString = null) : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
    
    #[\Ray\MediaQuery\Annotation\DbQuery('todo_list_fake_bool_param')]
    #[\Ray\MediaQuery\Annotation\Pager(perPage: 'perPage', template: '/{?page}')]
    public function getWithFakeBoolParam(int $perPage, ?\Ray\MediaQuery\FakeBool $fakeBool = null) : \Ray\MediaQuery\Pages
    {
        return $this->_intercept(func_get_args(), __FUNCTION__);
    }
}
