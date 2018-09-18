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
use app\home\model\ArticleCat;

error_reporting(0);
class Base extends Controller
{

    public $session_id;
    public $cateTrre = array();
    public $U_L_C;//User language currency info

    /**
     * init
     * Date: 2018/9/11 10:45
     * Author: Ning <nono0903@gmail.com>
     */
    public function initialize()
    {
        $this->U_L_C = $this->checkU_L_C(); //todo 此方法之前不能有任何可执行代码

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
     * 每次会话验证币种,语种
     * @return array
     * Date: 2018/9/18 18:19
     * Author: Ning <nono0903@gmail.com>.
     */
    public function checkU_L_C()
    {
        if(cookie('?U_L_C')){
            $result = JWT_decode(cookie('U_L_C'));
            if($result&&in_array($result['data']['lang'],config('lang_list'))&&in_array($result['data']['currency'],array_values(config('myconf.currency')))){
                $result['data']['lang']!=cookie('think_var')&&cookie('think_var',$result['data']['lang']);//被篡改后重新设置
                $result['data']['currency']!=cookie('curr')&&cookie('curr',$result['data']['currency']);
                return $result['data'];
            }
        }
        cookie('curr','null');
        return $this->setULC();
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

        if(!cookie('curr')||in_array(cookie('curr'),array_values(config('myconf.currency')))){//选择币种信息
            cookie('curr',config("myconf.currency.".cookie('think_var')),86400);
        }

        $ULC_info =['lang'=>cookie('think_var'),'currency'=>config("myconf.currency.".cookie('think_var'))];
        cookie('U_L_C',JWT_encode($ULC_info,'864000'));//设定信息保留10天
        return $ULC_info;

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

        $goods_category_tree = get_goods_category_tree($this->U_L_C['lang']);
        $this->cateTrre = $goods_category_tree;

        $this->assign('goods_category_tree', $goods_category_tree);
        $this->assign('navigation', get_hear_navigation($this->U_L_C['lang']));

        $brand_list = DB::table('brand')
            ->cache(true)
            ->field('id,name,parent_cat_id,logo,is_hot')
            ->where("parent_cat_id",">","0")
            ->select();
//        dump($brand_list);
        $this->assign('brand_list', $brand_list);

        $this->assign('tpshop_config', $glob_config);
        $user = session('user');
        $this->assign('username', $user['nickname']);


        $article = getArticle($this->U_L_C['lang']);
        $this->assign('article_list',$article);


        //PC端首页"手机端、APP二维码"
        $store_logo = $glob_config['shop_info_store_logo'];
        $store_logo ? $head_pic = $store_logo : $head_pic = '/public/static/images/logo/pc_home_logo_default.png';
        $mobile_url = "http://{$_SERVER['HTTP_HOST']}" . U('Mobile/index/app_down');
        $this->assign('head_pic', "http://{$_SERVER['HTTP_HOST']}/" . $head_pic);
        $this->assign('mobile_url', $mobile_url);
    }



}
