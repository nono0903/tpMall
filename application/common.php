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
        ->field('id,name,level,parent_id,mobile_name,is_hot')
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


/**
 * 获取首页导航
 * @param string $lang
 * @return mixed
 * Date: 2018/9/18 18:33
 * Author: Ning <nono0903@gmail.com>
 */
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

/**
 * 获取底部文章导航
 * @param string $lang
 * @return array
 * Date: 2018/9/18 22:29
 * Author: Ning <nono0903@gmail.com>
 */
function getArticle($lang = ''){
    $lang = $lang?$lang:config('default_lang');

    $footArticle = Cache::get($lang.'footArticle');
    if(!$footArticle){
        $article_data = Db::table('article')
            ->alias('a')
            ->leftJoin('article_cat c','a.cat_id=c.cat_id')
            ->field('a.article_id,a.title,c.cat_name')
            ->where([['c.lang_tag','=',$lang],['c.show_in_nav','=',1],['a.is_open','=',1]])
            ->order('c.sort_order,a.sort_order')
            ->select();
        $footArticle = [];
        foreach ($article_data as $v){
            $footArticle[$v['cat_name']][]=$v;
        }
        Cache::set($lang.'footArticle',$footArticle);
    }

    return $footArticle;
}




/**
 * 获取用户当前语言
 * @return mixed
 * Date: 2018/9/18 18:33
 * Author: Ning <nono0903@gmail.com>
 */
function getUserLang(){
    $language = JWT_decode(cookie('U_L_C'))['data']['lang'];
    $lang = $language ? $language : config('default_lang');
    return $lang;
}


/**
 * 获取广告信息
 * @param $pid 广告位id
 * @param int $limit
 * @param string $order
 * @return mixed
 * Date: 2018/9/18 18:33
 * Author: Ning <nono0903@gmail.com>
 */
function html_ad($pid,$limit=1,$order='desc'){

    $res = M('ad')
        ->where([
            ['lang_tag','=',getUserLang()],
            ['pid','=',$pid],
            ['enabled ','=',1],
            ['start_time','<',strtotime(date('Y-m-d H:00:00'))],
            ['end_time','>',strtotime(date('Y-m-d H:00:00'))]])
        ->order('orderby '.$order)
        ->Cache(true,GlOB_CACHE_TIME)
        ->limit($limit)
        ->select();


    return $res;

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
/**
 *   实现中文字串截取无乱码的方法
 */
function getSubstr($string, $start, $length) {
    if(mb_strlen($string,'utf-8')>$length){
        $str = mb_substr($string, $start, $length,'utf-8');
        return $str.'...';
    }else{
        return $string;
    }
}


/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thum_images($goods_id, $width, $height)
{
    return '';
    if (empty($goods_id)) return '';

    //判断缩略图是否存在
    $path = UPLOAD_PATH."goods/thumb/$goods_id/";
    $goods_thumb_name = "goods_thumb_{$goods_id}_{$width}_{$height}";

    // 这个商品 已经生成过这个比例的图片就直接返回了
    if (is_file($path . $goods_thumb_name . '.jpg')) return '/' . $path . $goods_thumb_name . '.jpg';
    if (is_file($path . $goods_thumb_name . '.jpeg')) return '/' . $path . $goods_thumb_name . '.jpeg';
    if (is_file($path . $goods_thumb_name . '.gif')) return '/' . $path . $goods_thumb_name . '.gif';
    if (is_file($path . $goods_thumb_name . '.png')) return '/' . $path . $goods_thumb_name . '.png';
    $original_img = Db::name('goods')->where("goods_id", $goods_id)->cache(true, 30, 'original_img_cache')->value('original_img');
    if (empty($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }

    $ossClient = new \app\common\logic\OssLogic;
    if (($ossUrl = $ossClient->getGoodsThumbImageUrl($original_img, $width, $height))) {
        return $ossUrl;
    }

    $original_img = '.' . $original_img; // 相对路径
    if (!is_file($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }

    try {
        require_once 'vendor/topthink/think-image/src/Image.php';
        require_once 'vendor/topthink/think-image/src/image/Exception.php';
        if(strstr(strtolower($original_img),'.gif'))
        {
            require_once 'vendor/topthink/think-image/src/image/gif/Encoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Decoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Gif.php';
        }
        $image = \think\Image::open($original_img);

        $goods_thumb_name = $goods_thumb_name . '.' . $image->type();
        // 生成缩略图
        !is_dir($path) && mkdir($path, 0777, true);
        $image->thumb($width, $height, 2)->save($path . $goods_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
        $img_url = '/' . $path . $goods_thumb_name;

        return $img_url;
    } catch (think\Exception $e) {

        return $original_img;
    }
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img, $goods_id, $width, $height)
{
    //判断缩略图是否存在
    $path = UPLOAD_PATH."goods/thumb/$goods_id/";
    $goods_thumb_name = "goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";

    //这个缩略图 已经生成过这个比例的图片就直接返回了
    if (is_file($path . $goods_thumb_name . '.jpg')) return '/' . $path . $goods_thumb_name . '.jpg';
    if (is_file($path . $goods_thumb_name . '.jpeg')) return '/' . $path . $goods_thumb_name . '.jpeg';
    if (is_file($path . $goods_thumb_name . '.gif')) return '/' . $path . $goods_thumb_name . '.gif';
    if (is_file($path . $goods_thumb_name . '.png')) return '/' . $path . $goods_thumb_name . '.png';

    $ossClient = new \app\common\logic\OssLogic;
    if (($ossUrl = $ossClient->getGoodsAlbumThumbUrl($sub_img['image_url'], $width, $height))) {
        return $ossUrl;
    }

    $original_img = '.' . $sub_img['image_url']; //相对路径
    if (!is_file($original_img)) {
        return '/public/images/icon_goods_thumb_empty_300.png';
    }

    try {
        require_once 'vendor/topthink/think-image/src/Image.php';
        require_once 'vendor/topthink/think-image/src/image/Exception.php';
        if(strstr(strtolower($original_img),'.gif'))
        {
            require_once 'vendor/topthink/think-image/src/image/gif/Encoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Decoder.php';
            require_once 'vendor/topthink/think-image/src/image/gif/Gif.php';
        }
        $image = \think\Image::open($original_img);

        $goods_thumb_name = $goods_thumb_name . '.' . $image->type();
        // 生成缩略图
        !is_dir($path) && mkdir($path, 0777, true);
        // 参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
        $image->thumb($width, $height, 2)->save($path . $goods_thumb_name, NULL, 100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
        $img_url = '/' . $path . $goods_thumb_name;

        return $img_url;
    } catch (think\Exception $e) {

        return $original_img;
    }
}



