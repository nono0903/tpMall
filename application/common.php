<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}


/**
 * @param mixed $lang language tags
 * @return array
 * Date: 2018/9/12 16:35
 * Author: Ning <nono0903@gmail.com>
 */
function get_goods_category_tree($lang = ''){
    $lang = $lang?$lang:config('default_lang');
    $result = Cache::get($lang.'category_list');
    if(!empty($result)) return $result;

    $tree = $arr = $result = array();

    $cat_list = Db::table('goods_category')
        ->where([['is_show','=',1],['lang_tag','=',$lang]])
        ->order('sort_order')
        ->field('id,name,level,parent_id,mobile_name')
        ->select();//所有分类


    if($cat_list){
        foreach ($cat_list as $val){
            if($val['level'] == 2){
                $arr[$val['parent_id']][] = $val;
            }
            if($val['level'] == 3){
                $crr[$val['parent_id']][] = $val;
            }
            if($val['level'] == 1){
                $tree[] = $val;
            }
        }

        foreach ($arr as $k=>$v){
            foreach ($v as $kk=>$vv){
                $arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
            }
        }

        foreach ($tree as $val){
            $val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
            $result[$val['id']] = $val;
        }
    }
    Cache::set($lang.'category_list',$result);
    return $result;
}



function get_hear_navigation($lang = ''){
    $lang = $lang?$lang:config('default_lang');
    $navigation = Cache::get($lang.'navigation');

    if(empty($navigation)){

        $navigation = Db::table('navigation')
            ->where([['is_show','=',1],['lang_tag','=',$lang]])
            ->order('sort DESC')
            ->field('name,url,is_new')
            ->select();
        Cache::set($lang.'navigation',$navigation);
    }
   return $navigation;

}


function ad(){

}


/**
 * 获取缓存或者更新缓存
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return array or string or bool
 */
function globCache($config_key,$data = array()){

    $param = explode('.', $config_key);
    if(empty($data)){
        $config = Cache::get($param[0]);//直接获取缓存文件
        if(empty($config)){
            //缓存文件不存在就读取数据库
            $res = Db::table('config')->where("inc_type",$param[0])->select();
            if($res){
                foreach($res as $k=>$val){
                    $config[$val['name']] = $val['value'];
                }
                Cache::set($param[0],$config);
            }
        }

        if(count($param)>1){
            return $config[$param[1]];
        }else{
            return $config;
        }
    }else{
        //更新缓存
        $result =  Db::table('config')->where("inc_type", $param[0])->select();
        if($result){
            foreach($result as $val){
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k=>$v){
                $newArr = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
                if(!isset($temp[$k])){
                    DB::table('config')->insert($newArr);//新key数据插入数据库
                }else{
                    if($v!=$temp[$k])
                        Db::table('config')->where("name", $k)->update($newArr);//缓存key存在且值有变更新此项
                }
            }
            //更新后的数据库记录
            $newRes = Db::table('config')->where("inc_type", $param[0])->select();
            foreach ($newRes as $rs){
                $newData[$rs['name']] = $rs['value'];
            }
        }else{
            foreach($data as $k=>$v){
                $newArr[] = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
            }
            Db::table('config')->insertAll($newArr);
            $newData = $data;
        }
        return Cache::set($param[0],$newData);
    }
}

/**
 * @param $data mixed body
 * @param int $time 过期时间
 * @return string token
 */
function JWT_encode($data,$time=3600){
    $data = json_encode($data);
    $builder = new Lcobucci\JWT\Builder();
    $signer = new Lcobucci\JWT\Signer\Hmac\Sha256();
    $secret =(string)config('myconf.jwt_secret');

    $token = $builder->setIssuer(config('app_host')) // Configures the issuer (iss claim)
    ->setAudience(config('app_host')) // Configures the audience (aud claim)
    ->setId(session_id() , true) // Configures the id (jti claim), replicating as a header item
    ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
//    ->setNotBefore(time() + 60) // Configures the time that the token can be used (nbf claim)
    ->setExpiration(time() + $time) // Configures the expiration time of the token (exp claim)
    ->set('data', $data) // Configures a new claim, called "uid"
    ->sign($signer, $secret) // creates a signature using "testing" as key
    ->getToken(); // Retrieves the generated token

    return strrev((string)$token);

}

/**
 * 验证解析 JWT_Token
 * @param $token string 字符串
 * @return array
 */
function JWT_decode($token){

    $token = strrev($token);
    if(!$parse = (new  Lcobucci\JWT\Parser())->parse($token)) return false;
    $signer = new Lcobucci\JWT\Signer\Hmac\Sha256();
    $secret =(string)config('myconf.jwt_secret');

    //验证token合法性
    if (!$parse->verify($signer, $secret))  $data = ['status'=>'0','msg' => 'Invalid token'];
    //验证是否已经过期
    if ($parse->isExpired())  $data = ['status'=>'0','msg' => 'Already expired'];
    //获取数据
    else $data = ['status'=>1,'data'=>json_decode($parse->getClaim('data'),true)];

    return $data;

}



