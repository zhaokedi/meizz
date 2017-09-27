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

class InviteController extends BaseController{

    
    public function config(){
        
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览

        $info=tpCache('invite');

        $this->assign('info',$info);// 赋值分页输出
        $this->display();
    }

    public function configHandle(){
        $data = I('post.');
        tpCache('invite',$data);

        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Invite/config');
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
        $this->success("操作成功",$referurl);

    }
    public function inviteList(){
        $account_log=  M('account_log');
        $count = $account_log->where('1=1 and type =1 and is_now =1')->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数

        $list = $account_log->order('log_id DESC')->limit($Page->firstRow.','.$Page->listRows)->where('type =1 and is_now =1')->select();
        if($list){
            $ids=get_arr_column($list,'user_id');
            $ids2=get_arr_column($list,'sid');
            $ids3=array_merge($ids,$ids2);
            $ids3=array_unique($ids3);
            $userlist=M('users')->where(" user_id in (".implode(",",$ids3).")" )->getField("user_id,nickname");
            $this->assign('userlist',$userlist);
        }

        $this->assign('list',$list);// 赋值数据集

        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    public function commissionList(){
        $account_log=  M('account_log');
        $count = $account_log->where('1=1 and type =2 and is_now =1')->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $account_log->order('log_id DESC')->limit($Page->firstRow.','.$Page->listRows)->where('type =2 and is_now =1')->select();
        if($list){
            $ids=get_arr_column($list,'user_id');
            $ids2=get_arr_column($list,'sid');
            $ids3=array_merge($ids,$ids2);
            $ids3=array_unique($ids3);
            $userlist=M('users')->where(" user_id in (".implode(",",$ids3).")" )->getField("user_id,nickname");
            $this->assign('userlist',$userlist);
        }


        $this->assign('list',$list);// 赋值数据集
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    public function positionList(){
        $Position =  M('ad_position');
        $count = $Position->where('1=1')->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Position->order('position_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);// 赋值数据集                
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    public function adHandle(){
    	$data = I('post.');
    	$data['start_time'] = strtotime($data['begin']);
    	$data['end_time'] = strtotime($data['end']);
    	
    	if($data['act'] == 'add'){
    		$r = D('ad')->add($data);
    	}
    	if($data['act'] == 'edit'){
    		$r = D('ad')->where('ad_id='.$data['ad_id'])->save($data);
    	}
    	
    	if($data['act'] == 'del'){
    		$r = D('ad')->where('ad_id='.$data['del_id'])->delete();
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
    
    public function positionHandle(){
        $data = I('post.');
        if($data['act'] == 'add'){
            $r = M('ad_position')->add($data);
        }
        
        if($data['act'] == 'edit'){
        	$r = M('ad_position')->where('position_id='.$data['position_id'])->save($data);
        }
        
        if($data['act'] == 'del'){
        	if(M('ad')->where('pid='.$data['position_id'])->count()>0){
        		$this->error("此广告位下还有广告，请先清除",U('Admin/Ad/positionList'));
        	}else{
        		$r = M('ad_position')->where('position_id='.$data['position_id'])->delete();
        		if($r) exit(json_encode(1));
        	}
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Ad/positionList');
        if($r){
        	$this->success("操作成功",$referurl);
        }else{
        	$this->error("操作失败",$referurl);
        }
    }
    
    public function changeAdField(){
    	$data[$_REQUEST['field']] = I('GET.value');
    	$data['ad_id'] = I('GET.ad_id');
    	M('ad')->save($data); // 根据条件保存修改的数据
    }
}