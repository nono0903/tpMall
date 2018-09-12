<?php
/**
 * Ajax调用接口文件
 * User: Ning
 * Date: 2018/9/5
 * Time: 16:11
 */

namespace app\home\controller;
use think\Controller;


class Api extends Controller
{
    /**
     * 设定语种
     */
    public function setLang()
    {

        $lang = input('lang',config('default_lang'));

        if($lang){

            cookie('think_var',$lang,86400);
            cookie('curr',config("myconf.currency.$lang"),86400);
            cookie('U_L_C',JWT_encode(['lang'=>$lang,'currency'=>config("myconf.currency.$lang")],864000),'864000');//设定登录信息保留10天
            return true;

        }
        
   }

    /**
     * 设定币种
     */
    public function setCurr()
    {
        $currency = input('curr');
        cookie('curr',$currency,86400);
        cookie('U_L_C',JWT_encode(['lang'=>cookie('think_var'),'currency'=>$currency],864000),'864000');//设定登录信息保留10天
        return true;

   }

    public function test()
    {
        return 'test to api';

   }

}