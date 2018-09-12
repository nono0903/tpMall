<?php
/**
 * 前端页面路由定义
 * User: Ning
 * Date: 2018/9/5
 * Time: 16:19
 */

Route::get('/','home/Index/index');//首页
Route::get('/index','home/Index/index');//首页
route::post('setLang','home/Api/setLang');//设定语言
route::post('setCurr','home/Api/setCurr');//设定币种
//route::post('test','/home/Api/setCurr');//设定币种



Route::rule('hello', function(){
    return 'this is test';
});

Route::get('test','home/Api/test');

return [

];
