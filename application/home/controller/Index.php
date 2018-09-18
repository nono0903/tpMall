<?php
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Cache;
error_reporting(0);
class Index extends Base
{

    public function index()
    {
        // 如果是手机跳转到 手机模块
//        if(isMobile()){
//            redirect(U('Mobile/Index/index'));
//        }

        $lang = $this->U_L_C['lang'];

        $hot_goods = $hot_cate = $cateList = $recommend_goods = array();

        $index_hot_goods = Db::table('goods')
            ->alias('g')
            ->leftJoin('goods_category c','g.cat_id = c.id')
            ->field('g.goods_name,g.goods_id,g.goods_remark,g.shop_price,g.market_price,g.cat_id,c.parent_id_path,c.name')
            ->where([['g.lang_tag','=',$lang],['g.status','=',6],['g.is_on_sale','=',1],['g.is_hot','=',1]])
            ->order('g.sort')
            ->cache(true,GlOB_CACHE_TIME)
            ->select();

        if($index_hot_goods){
            foreach($index_hot_goods as $val){
                $cat_path = explode('_', $val['parent_id_path']);
                $hot_goods[$cat_path[1]][] = $val;
            }
        }

        $index_recommend_goods = Db::table('goods')
            ->alias('g')
            ->leftJoin('goods_category c','g.cat_id = c.id')
            ->field('g.goods_name,g.goods_id,g.goods_remark,g.shop_price,g.market_price,g.cat_id,c.parent_id_path,c.name')
            ->where([['g.lang_tag','=',$lang],['g.status','=',6],['g.is_on_sale','=',1],['g.is_recommend','=',1]])
            ->order('g.sort')
            ->cache(true,GlOB_CACHE_TIME)
            ->select();

        if($index_recommend_goods){
            foreach($index_recommend_goods as $va){
                $cat_path2 = explode('_', $va['parent_id_path']);
                $recommend_goods[$cat_path2[1]][] = $va;
            }
        }


        $hot_category = Db::table('goods_category')
        ->where("is_hot=1 and level=3 and is_show=1")
        ->where([['lang_tag','=',$lang],['level','=',3],['is_hot','=',1],['is_show','=',1]])
        ->Cache(true,GlOB_CACHE_TIME)
        ->select();//热门三级分类

        foreach ($hot_category as $v){
            $cat_path = explode('_', $v['parent_id_path']);
            $hot_cate[$cat_path[1]][] = $v;
        }

        foreach ($this->cateTrre as $k=>$v){

            if($v['is_hot']==1){
                $v['hot_goods'] = empty($hot_goods[$k]) ? '' : $hot_goods[$k];
                $v['recommend_goods'] = empty($recommend_goods[$k]) ? '' : $recommend_goods[$k];
                $v['hot_cate'] = empty($hot_cate[$k]) ? array() : $hot_cate[$k];
                $cateList[] = $goods_category_tree[] = $v;

            }else{
                $goods_category_tree[] = $v;
            }
        }

        $this->assign('cateList',$cateList);
        $this->assign('goods_category_tree',$goods_category_tree);
        return $this->fetch();


    }

    public function test(){
        return 123321;
    }


}
