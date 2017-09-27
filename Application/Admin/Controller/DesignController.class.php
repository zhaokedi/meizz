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

class DesignController extends BaseController{
    public function designedit(){
        $act = I('GET.act','add');
        $ad_id = I('GET.id');
        $ad_info = array();
        if($ad_id){
            $ad_info = D('design')->where('id='.$ad_id)->find();
            $ad_info['server_time']=unserialize($ad_info['server_time']);
            $ad_info['job']=unserialize($ad_info['job']);
            $ad_info['server']=unserialize($ad_info['server']);

        }
        if($act == 'add')          
           $ad_info['pid'] = $_GET['pid'];                
        $research_list = D('research_list')->where('1=1')->select();
        $designImages = M("design_images")->where('did ='.I('GET.id',0))->select();
        $this->assign('design_images',$designImages);  // 商品相册
//        show_bug($designImages);
        $this->assign('info',$ad_info);
        $this->assign('act',$act);
        $this->assign('research_list',$research_list);
        $this->display();
    }
    //问题列表
    public function design(){
        
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
            
        $design =  M('design');
        $where = "1=1";

        $keywords = I('keywords',false);
        if($keywords){
        	$where = "name like '%$keywords%'";
        }
        $count = $design->where($where)->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $design->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
//        $list = array();
//        show_bug($list);

//        $ad_position_list = M('research_list')->getField("id,title,is_star");
//        $this->assign('ad_position_list',$ad_position_list);//广告位
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
    
    public function designHandle(){
    	$data = I('post.');


    	if($data['act'] == 'add'){


            $data['job']=serialize(I('job'));
            $data['server_time']=serialize(I('server_time'));
            $data['server']=serialize(I('server'));
    		$r = D('design')->add($data);
            $this->editimage($r);
    	}
    	if($data['act'] == 'edit'){

            $data['job']=serialize(I('job'));
            $data['server_time']=serialize(I('server_time'));
            $data['server']=serialize(I('server'));
    		$r = D('design')->where('id='.$data['id'])->save($data);

            // 商品图片相册  图册
            $this->editimage($data['id']);


    	}
    	if($data['act'] == 'del'){
    		$r = D('design')->where('id='.$data['del_id'])->delete();
    		if($r) exit(json_encode(1));
    	}

    	$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Research/question');
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览

    	if($r>=0){
    		$this->success("操作成功",$referurl);
    	}else{
    		$this->error("操作失败",$referurl);
    	}
    }
    public function editimage($id){
        if(count($_POST['design_images']) > 1) {
//            array_unshift($_POST['design_images'], $_POST['original_img']); // 商品原始图 默认为 相册第一张图片
//            array_pop($_POST['design_images']); // 弹出最后一个
            $goodsImagesArr = M('design_images')->where("did = $id")->getField('img_id,image_url'); // 查出所有已经存在的图片
           if(!empty($goodsImagesArr)){
               // 删除图片
               foreach ($goodsImagesArr as $key => $val) {
                   if (!in_array($val, $_POST['design_images']))
                       M('design_images')->where("img_id = {$key}")->delete(); // 删除所有状态为0的用户数据
               }
           }
            // 添加图片
            foreach ($_POST['design_images'] as $key => $val) {
                if ($val == null) continue;
                if (!in_array($val, $goodsImagesArr)) {
                    $data2 = array(
                        'did' => $id,
                        'image_url' => $val,
                    );
                    M("design_images")->data($data2)->add();; // 实例化User对象
                }
            }
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