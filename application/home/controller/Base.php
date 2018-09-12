<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * Date: 2018/9/3
 * Time: 18:12
 */

namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Session;

error_reporting(0);
class Base extends Controller
{

    public $session_id;
    public $cateTrre = array();
    public $U_L_C;

    /**
     * init
     * Date: 2018/9/11 10:45
     * Author: Ning <nono0903@gmail.com>
     */
    public function initialize()
    {


        if(!cookie('U_L_C')||!cookie('think_var')||!cookie('curr')){

            if(!cookie('U_L_C')||JWT_decode(cookie('U_L_C'))['status']!=1){

                $this->U_L_C = $this->setULC();

            }else{
                $this->U_L_C = JWT_decode(cookie('U_L_C'));
                cookie('think_var',$this->U_L_C['data']['lang'],86400);
                cookie('curr',$this->U_L_C['data']['currency'],86400);
                $this->U_L_C = $this->setULC();

            }


        }else{
            $this->U_L_C = JWT_decode(cookie('U_L_C'));
        }


        if (input("unique_id")) {           // 兼容手机app
            session_id(input("unique_id"));
            Session::start();
        }
        header("Cache-control: private");
        $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID', $this->session_id); //将当前的session_id保存为常量，供其它方法调用

        // 判断当前用户是否手机
        if (isMobile())
            cookie('is_mobile', '1', 3600);
        else
            cookie('is_mobile', '0', 3600);

        $this->public_assign();
    }


    /**
     * setUser language and currency info
     * @return array
     * Date: 2018/9/12 10:45
     * Author: Ning <nono0903@gmail.com>
     */
    public function setULC()
    {
        if(!cookie('think_var')||!in_array(cookie('think_var'),config('lang_list'))){//没有语种,或非法定义语种
            $http_lang = strtolower(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]);//获取通讯语言
            $http_lang=in_array($http_lang,config('lang_list'))?$http_lang:config('default_lang');
            cookie('think_var',$http_lang,86400);
        }

        if(!cookie('curr')){//选择币种信息
            cookie('curr',config("myconf.currency.".cookie('think_var')),86400);
        }

        $ULC_info =['lang'=>cookie('think_var'),'currency'=>config("myconf.currency.".cookie('think_var'))];
        cookie('U_L_C',JWT_encode($ULC_info,'864000'));//设定信息保留10天
        return ['data'=>$ULC_info];

    }


    /**
     * 保存公告变量到 smarty中 比如 导航
     */
    public function public_assign()
    {

        $glob_config = array();
        $config = DB::table('config')
            ->cache(true, GlOB_CACHE_TIME)
//            ->where()
            ->select();//主要针对中文优化

        foreach ($config as $k => $v) {
            if ($v['name'] == 'hot_keywords') {
                $glob_config['hot_keywords'] = explode('|', $v['value']);
            }
            $glob_config[$v['inc_type'] . '_' . $v['name']] = $v['value'];
        }

        $goods_category_tree = get_goods_category_tree();
        $this->cateTrre = $goods_category_tree;
        $this->assign('goods_category_tree', $goods_category_tree);
        $brand_list = DB::table('brand')->cache(true)->field('id,name,parent_cat_id,logo,is_hot')->where("parent_cat_id",">","0")->select();
        $this->assign('brand_list', $brand_list);
        $this->assign('tpshop_config', $glob_config);
        $user = session('user');
        $this->assign('username', $user['nickname']);

        //PC端首页"手机端、APP二维码"
        $store_logo = globCache('shop_info.shop_info_store_logo');
        $store_logo ? $head_pic = $store_logo : $head_pic = '/public/static/images/logo/pc_home_logo_default.png';
        $mobile_url = "http://{$_SERVER['HTTP_HOST']}" . U('Mobile/index/app_down');
        $this->assign('head_pic', "http://{$_SERVER['HTTP_HOST']}/" . $head_pic);
        $this->assign('mobile_url', $mobile_url);
    }
}
