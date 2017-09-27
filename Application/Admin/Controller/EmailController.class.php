<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃      
 * Date: 2015-09-21
 */

namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Think\AjaxPage;
use Think\Page;
class EmailController extends BaseController{



    public function follow(){
//        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览

        $goods_collect =  M('goods_collect');
        $where = "1 ";
        $condition = "1 ";
        if(I('goods_id')){
        	$where .= "and c.goods_id=".I('goods_id');
            $condition="goods_id=".I('goods_id');
        	$this->assign('goods_id',I('goods_id'));
        }
        $keywords = I('keywords',false);
        if($keywords){
            $where = " goods_name like '%$keywords%'";
            $condition = " goods_name like '%$keywords%'";
        }

        $count = $goods_collect->where($condition)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
//        $res = $goods_collect->where($where)->order('goods_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $where.=" and  c.user_id = u.user_id";
        $res = M()->table('users u,goods_collect c')->field('u.email,u.nickname,c.*')->where($where)->order('c.goods_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();


        $show = $Page->show();// 分页显示输出
        $this->assign('list',$res);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    
    public function goodslist(){
        
//        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
            
        $goods =  M('goods');
        $where = "1=1";
//        if(I('pid')){
//        	$where = "pid=".I('pid');
//        	$this->assign('pid',I('pid'));
//        }
        $keywords = I('keywords',false);
        if($keywords){
        	$where = "goods_name like '%$keywords%'";
        }
        $count = $goods->where($where)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $goods->field('goods_name,goods_sn,goods_id,last_update,sort')->where($where)->order('goods_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($res as $k=>$v){
            $res[$k]['follownum']=M('goods_collect')->where(array('goods_id'=>$v['goods_id']))->count();
        }

        $show = $Page->show();// 分页显示输出
        $this->assign('list',$res);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    

    
    public function changeAdField(){
    	$data[$_REQUEST['field']] = I('GET.value');
    	$data['ad_id'] = I('GET.ad_id');
    	M('ad')->save($data); // 根据条件保存修改的数据
    }

    public function op(){
        $type = I('post.type');
        $selected_id = I('post.selected');
//

//        logResult1(var_export($res,true));exit;

        if(!in_array($type,array('del','show','hide','send_email')) || !$selected_id)
            $this->error('非法操作');
        $where = "comment_id IN ({$selected_id})";
        if($type == 'del'){
            //删除回复
            $where .= " OR parent_id IN ({$selected_id})";
            $row = M('goods_collect')->where($where)->delete();
//            exit(M()->getLastSql());
        }
        if($type=='send_email'){
            $title=I('post.title');
            $content=I('post.content');
            if(empty($content))  $this->error('邮件能容不能为空');
            $where = "1 ";
            $where.=" and  c.user_id = u.user_id and c.collect_id in ($selected_id)";
            $res = M()->table('users u,goods_collect c')->field('u.email,u.nickname,c.*')->where($where)->select();

            foreach($res as $k=>$v){
                send_email($v['email'],$title,$content);
//               $res= send_email('3048327507@qq.com',$title,$content);

            }
            $this->success('邮件群发成功');
        }
        if($type == 'show'){
            $row = M('goods_collect')->where($where)->save(array('is_show'=>1));
        }
        if($type == 'hide'){
            $row = M('goods_collect')->where($where)->save(array('is_show'=>0));
        }
        if(!$row)
            $this->error('操作失败');
        $this->success('操作成功');

    }
}