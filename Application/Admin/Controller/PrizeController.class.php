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

class PrizeController extends BaseController{
    public function index(){
        $this->display();
    }
    public function prize(){
        $act = I('GET.act','add');
        $prize_id = I('GET.prize_id');
        $prize_info = array();
        if($prize_id){
            $prize_info = D('prize')->where('prize_id='.$prize_id)->find();
            $prize_info['starttime'] = date('Y-m-d',$prize_info['starttime']);
            $prize_info['endtime'] = date('Y-m-d',$prize_info['endtime']);
        }
        if($act == 'add')          
           $prize_info['pdid'] = $_GET['pdid'];
        $prize_draw = D('prize_draw')->where('1=1')->select();
        $this->assign('info',$prize_info);
        $this->assign('act',$act);
        $this->assign('prize_draw',$prize_draw);
        $this->display();
    }
    
    public function prizeList(){
        
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
            
        $Ad =  M('prize');
        $where = "1=1";
        if(I('pdid')){
        	$where = "pdid=".I('pdid');
        	$this->assign('pdid',I('pdid'));
        }
        $keywords = I('keywords',false);
        if($keywords){
        	$where = "name like '%$keywords%'";
        }
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->order('pdid desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = array();
        if($res){
        	$media = array('图片','文字','flash');
        	foreach ($res as $val){
        		$val['media_type'] = $media[$val['media_type']];        		
        		$list[] = $val;
        	}
        }
                                     
        $ad_position_list = M('prize_draw')->getField("id,name,is_open");
        $this->assign('prize_draw_list',$ad_position_list);//活动
        $show = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    public function prizedraw(){
        $act = I('GET.act','add');
        $id = I('GET.id');
        $info = array();
        if($id){
            $info = D('prize_draw')->where('id='.$id)->find();
            $this->assign('info',$info);
        }
        $this->assign('act',$act);
        $this->display();
    }
    
    public function prizedrawList(){
        $Position =  M('prize_draw');
        $count = $Position->where('1=1')->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Position->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);// 赋值数据集                
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    public function prizeHandle(){
    	$data = I('post.');

    	if($data['act'] == 'add'){
    		$r = D('prize')->add($data);
    	}
    	if($data['act'] == 'edit'){
    		$r = D('prize')->where('prize_id='.$data['prize_id'])->save($data);
    	}
    	
    	if($data['act'] == 'del'){
    		$r = D('prize')->where('prize_id='.$data['del_id'])->delete();
    		if($r) exit(json_encode(1));
    	}
    	$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/adList');
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
        
    	if($r){
    		$this->success("操作成功",$referurl);
    	}else{
    		$this->error("操作失败",$referurl);
    	}
    }
    
    public function prizedrawHandle(){
        $data = I('post.');
        $data['starttime'] = strtotime($data['begin']);
        $data['endtime'] = strtotime($data['end']);
        if($data['act'] == 'add'){
            $r = M('prize_draw')->add($data);
        }
        
        if($data['act'] == 'edit'){
        	$r = M('prize_draw')->where('id='.$data['id'])->save($data);
        }
        
        if($data['act'] == 'del'){
        	if(M('prize')->where('pdid='.$data['id'])->count()>0){
        		$this->error("此活动下还有奖品，请先清除",U('Admin/Prize/prizedrawList'));
        	}else{
        		$r = M('prize_draw')->where('id='.$data['id'])->delete();
        		if($r) exit(json_encode(1));
        	}
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Prize/prizedrawList');
        if($r){
        	$this->success("操作成功",$referurl);
        }else{
        	$this->error("操作失败",$referurl);
        }
    }
    
    public function changeAdField(){
    	$data[$_REQUEST['field']] = I('GET.value');
    	$data['prize_id'] = I('GET.prize_id');
    	M('prize')->save($data); // 根据条件保存修改的数据
    }


    public function prize_user(){
        $act = I('GET.act','add');
        $prize_id = I('GET.id');
        $prize_info = array();
        if($prize_id){
            $prize_info = D('prize_list')->where('id='.$prize_id)->find();

        }
        if($act == 'add')
            $prize_info['pdid'] = $_GET['pdid'];
        $prize_draw = D('prize_draw')->where('1=1')->select();
        $this->assign('info',$prize_info);
        $this->assign('act',$act);
        $this->assign('prize_draw',$prize_draw);
        $this->display();
    }

    public function prize_user_List(){

        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览

        $Ad =  M('prize_list');

        $where = "1=1";
        $type=I('type');
        if($type!='' ){
            if($type !=-1){
                $where = "type=".I('type');
                $this->assign('type',I('type'));
            }
        }
//        $keywords = I('keywords',false);
//        if($keywords){
//            $where = "name like '%$keywords%'";
//        }
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->order('pdid desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = array();

        $uids=get_arr_column($res,"uid");

        if($uids)
             $userlist= M('users')->where("user_id in(".implode(",",$uids).")")->getField('user_id,name,mobile');


        $ad_position_list = M('prize_draw')->getField("id,name,is_open");
        $prize = M('prize')->getField("prize_id,name,chance");
        $this->assign('prize_draw_list',$ad_position_list);//活动
        $this->assign('prize',$prize);//活动

        $this->assign('user',$userlist);//活动
//        show_bug($userlist);
        $show = $Page->show();// 分页显示输出
        $this->assign('list',$res);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function prize_userHandle(){
        $data = I('post.');

        if($data['act'] == 'add'){
            $r = D('prize_list')->add($data);
        }
        if($data['act'] == 'edit'){
            $r = D('prize_list')->where('id='.$data['id'])->save($data);
        }

        if($data['act'] == 'del'){
            $r = D('prize_list')->where('id='.$data['del_id'])->delete();
            if($r) exit(json_encode(1));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/adList');
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览

        if($r){
            $this->success("操作成功",$referurl);
        }else{
            $this->error("操作失败",$referurl);
        }
    }
}