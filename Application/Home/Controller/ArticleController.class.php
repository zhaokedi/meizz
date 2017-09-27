<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace Home\Controller;
use Home\Logic\ArticleLogic;
use Think\Page;

class ArticleController extends BaseController {

    public function index(){
        $this->redirect('Article/detail');
    }

    /**
     * 文章内容页
     */
    public function detail(){
		$now_time =  time();
    	$article_id = I('article_id');
        $Article = M("article");
        if(!$article_id){
            $article_cat = D('article_cat')->where("show_in_nav=1")->order("cat_id desc")->find();
            if(empty($article_cat)){
                $this->error('暂无内容');
            }
            $article = D('article')->where("cat_id={$article_cat['cat_id']} and publish_time<{$now_time} and is_open=1")->order('article_id desc')->find();
            if(empty($article)){
                $this->error('暂无内容');
            }
            $Article->where("article_id = {$article['article_id']}")->setInc('click');   //浏览数加1
        }else{
            $Article->where("article_id = {$article_id}")->setInc('click');   //浏览数加1
            $article = $Article->where("article_id={$article_id} and publish_time<{$now_time} and is_open=1")->find();
            if(empty($article)){
                $this->error('找不到指定内容');
            }
        }
		$this->assign('detail',$article);
        $this->display();
    }
    public function shop(){
        $count =M('store')->where(array('status'=>1))->count();

        $page = new Page($count,8);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        $store= M('store')->where(array('status'=>1))->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('storelist',$store);
        $this->assign('page',$page);
        $this->display();
    }
    public function shopmap(){
        $id=I('id');
        $store= M('store')->find($id);
        $zuobiao=explode(",",$store['zuobiao']);
        $this->assign('lnt',$zuobiao[0]);
        $this->assign('lat',$zuobiao[1]);
        $this->assign('store',$store);
        $this->display();
    }
}