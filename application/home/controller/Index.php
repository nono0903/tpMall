<?php
namespace app\home\controller;
use think\Controller;
error_reporting(0);
class Index extends Base
{

    public function index()
    {
        error_reporting(0);

//        return 'this is index';
        return $this->fetch();

    }

    public function test(){
        return 123321;
    }


}
