<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
// | 不允许对程序代码以任何形式任何目的的再发布。
// +----------------------------------------------------------------------
// | Date: 2018/9/18 21:04
// +----------------------------------------------------------------------
// | Author: Ning <nono0903@gmail.com>
// +----------------------------------------------------------------------


namespace app\home\model;
use think\Model;

class Article extends Model
{
    protected $table = 'article';
    protected $pk = 'article_id';

    public function ArticleCat(){
        return $this->belongsTo('ArticleCat');
    }


}