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

class ResearchController extends BaseController{
    public function queedit(){
        $act = I('GET.act','add');
        $ad_id = I('GET.id');
        $ad_info = array();
        if($ad_id){
            $ad_info = D('research')->where('id='.$ad_id)->find();
            $ad_info['option']=unserialize($ad_info['option']);

        }
        if($act == 'add')          
           $ad_info['pid'] = $_GET['pid'];                
        $research_list = D('research_list')->where('1=1')->select();
        $this->assign('info',$ad_info);
        $this->assign('act',$act);
        $this->assign('research_list',$research_list);
        $this->display();
    }
    //问题列表
    public function question(){
        
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
            
        $Ad =  M('research');
        $where = "1=1";
        if(I('pid')){
        	$where = "pid=".I('pid');
        	$this->assign('pid',I('pid'));
        }
        $keywords = I('keywords',false);
        if($keywords){
        	$where = "title like '%$keywords%'";
        }
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->order('pid desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = array();
        if($res){
        	$media = array('图片','文字','flash');
        	foreach ($res as $val){
        		$val['media_type'] = $media[$val['media_type']];        		
        		$list[] = $val;
        	}
        }
                                     
        $ad_position_list = M('research_list')->getField("id,title,is_star");
        $this->assign('ad_position_list',$ad_position_list);//广告位 
        $show = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    public function research(){
        $act = I('GET.act','add');
        $position_id = I('GET.id');
        $info = array();
        if($position_id){
            $info = D('research_list')->where('id='.$position_id)->find();
            $this->assign('info',$info);
        }
        $this->assign('act',$act);
        $this->display();
    }
    //问卷列表
    public function researchlist(){
        $Position =  M('research_list');
        $count = $Position->where('1=1')->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Position->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);// 赋值数据集                
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    public function queHandle(){
    	$data = I('post.');
    	$question=I('question');

    	if($data['act'] == 'add'){

            foreach($question as $k=>$v){

                $a[$k]['sort']=$k;
                $a[$k]['que']=$v;
                $a[$k]['count']=0;
            }

            $data['option']=serialize($a);

    		$r = D('research')->add($data);
    	}
    	if($data['act'] == 'edit'){

            $res=M('research')->find($data['id']);
                foreach($question as $k=>$v){

                    $a[$k]['sort']=$k;
                    $a[$k]['que']=$v;
                    $a[$k]['count']=0;
                }
                $data['option']=serialize($a);
    		$r = D('research')->where('id='.$data['id'])->save($data);
    	}
    	if($data['act'] == 'del'){
    		$r = D('research')->where('id='.$data['del_id'])->delete();
    		if($r) exit(json_encode(1));
    	}

    	$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Research/question');
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
        
    	if($r){
    		$this->success("操作成功",$referurl);
    	}else{
    		$this->error("操作失败",$referurl);
    	}
    }
    
    public function researchHandle(){
        $data = I('post.');
        if($data['act'] == 'add'){
            $r = M('research_list')->add($data);
        }
        
        if($data['act'] == 'edit'){
        	$r = M('research_list')->where('id='.$data['id'])->save($data);
        }
        
        if($data['act'] == 'del'){
        	if(M('research')->where('pid='.$data['del_id'])->count()>0){
        		$this->error("此问卷下还有问题，请先清除",U('Admin/Research/researchlist'));
        	}else{
        		$r = M('research_list')->where('id='.$data['del_id'])->delete();
        		if($r) exit(json_encode(1));
        	}
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Research/researchlist');
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