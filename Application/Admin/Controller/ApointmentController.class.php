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
 * 专题管理
 * Date: 2016-03-09
 */

namespace Admin\Controller;
use Think\AjaxPage;
use Admin\Logic\GoodsLogic;

class ApointmentController extends BaseController {

    public function index(){
        $this->display();
    }
    

	
    public function storelist(){
    	$Ad =  M('store');
    	$p = I('p',1);
        
    	$count = $Ad->count();
    	$Page = new \Think\Page($count,10);        
    	$res = $Ad->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	if($res){
    		foreach ($res as $val){
    			$val['start_time'] = date('Y-m-d',$val['start_time']);
    			$val['end_time'] = date('Y-m-d',$val['end_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->display();
    }
    
    public function store(){
    	$act = I('GET.act','add');
		$store_id = I('get.id');
    	$store = array();

    	if($store_id){
			$store = D('store')->where('id='.$store_id)->find();
    		$act = 'edit';
    	}

    	$this->assign('info',$store);
    	$this->assign('act',$act);
    	$this->display();
    }

	public function apointlist(){
//        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
		$statusname=array('1'=>'待确认','2'=>'已确认','3'=>'预约完成','4'=>'预约取消',);
		$apointment =  M('apointment');
		$where = " 1 ";
		$condition = "1 ";
		if(I('store_id')){
			$where .= "and a.store_id=".I('store_id');
			$condition.=" and store_id=".I('store_id');
			$this->assign('store_id',I('store_id'));
		}
		$keywords = I('keywords',false);
		if($keywords){
			$where = " username like '%$keywords%'";
			$condition .= " and username like '%$keywords%'";
			$this->assign('keywords',$keywords);
		}

		$count = $apointment->where($condition)->count();// 查询满足要求的总记录数
		$Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
//        $res = $goods_collect->where($where)->order('goods_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
//		$where.=" and  a.store_id = s.id and a.type_id=t.id";
//		$res = M()->table('apointment a,store s,apointtype t')->field('s.name,s.address shopaddr,a.*,t.type_name')->where($where)->order('a.id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$sql="select s.name,s.address shopaddr,a.*,t.type_name from apointment a LEFT JOIN store s ON a.store_id = s.id LEFT JOIN apointtype t ON a.type_id=t.id where ".$where." order by a.id desc limit $Page->firstRow,$Page->listRows";
		$res =M()->query($sql);
		foreach($res as $k=>$v){
			$res[$k]['statusname']=$statusname[$v['status']];
		}
		$store_list = M('store')->Field("id,name")->select();
		$this->assign('store_list',$store_list);//门店

		$show = $Page->show();// 分页显示输出
		$this->assign('list',$res);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}

	public function apointedit(){

		$act = I('GET.act','add');
		$this->assign('act',$act);
		$id = I('GET.id');
		$apoint_info = array();
		if($id){
			$apoint_info = D('apointment')->where('id='.$id)->find();
			$this->assign('apoint_info',$apoint_info);
		}

		$this->display();
	}
	public function designlist(){
//        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览
		$statusname=array('1'=>'待确认','2'=>'已确认','3'=>'预约完成','4'=>'预约取消',);
		$apointment =  M('apointment_design');
		$where = " 1 ";
		$condition = "1 ";
		if(I('design_id')){
			$where .= "and design_id=".I('design_id');
			$condition.=" and design_id=".I('design_id');
			$this->assign('design_id',I('design_id'));
		}
		$keywords = I('keywords',false);
		if($keywords){
			$where .= " and username like '%$keywords%'";
			$condition .= " and username like '%$keywords%'";
			$this->assign('keywords',$keywords);
		}

		$count = $apointment->where($condition)->count();// 查询满足要求的总记录数
		$Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
//        $res = $goods_collect->where($where)->order('goods_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
//		$where.=" and  a.store_id = s.id and a.type_id=t.id";
//		$res = M()->table('apointment a,store s,apointtype t')->field('s.name,s.address shopaddr,a.*,t.type_name')->where($where)->order('a.id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$sql="select * from apointment_design   where ".$where." order by id desc limit $Page->firstRow,$Page->listRows";
//		show_bug($sql);
//		exit;
		$res =M()->query($sql);
		foreach($res as $k=>$v){
			$res[$k]['statusname']=$statusname[$v['status']];
		}
		$design_list = M('design')->Field("id,name")->select();
		$design_list=convert_arr_key($design_list,'id');
//		show_bug($design_list);
		$this->assign('design_list',$design_list);//设计师

		$show = $Page->show();// 分页显示输出
		$this->assign('list',$res);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}

	public function commentlist(){
//        delFile(RUNTIME_PATH.'Html'); // 先清除缓存, 否则不好预览

		$apointment =  M('comment_design');
		$where = " 1 ";
		$condition = "1 ";
		if(I('design_id')){
			$where .= "and design_id=".I('design_id');
			$condition.=" and design_id=".I('design_id');
			$this->assign('design_id',I('design_id'));
		}
//		$keywords = I('keywords',false);
//		if($keywords){
//			$where .= " and username like '%$keywords%'";
//			$condition .= " and username like '%$keywords%'";
//			$this->assign('keywords',$keywords);
//		}

		$count = $apointment->where($condition)->count();// 查询满足要求的总记录数
		$Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
//        $res = $goods_collect->where($where)->order('goods_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
//		$where.=" and  a.store_id = s.id and a.type_id=t.id";
//		$res = M()->table('apointment a,store s,apointtype t')->field('s.name,s.address shopaddr,a.*,t.type_name')->where($where)->order('a.id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$sql="select * from comment_design   where ".$where." order by comment_id desc limit $Page->firstRow,$Page->listRows";
//		show_bug($sql);
//		exit;
		$res =M()->query($sql);
//		foreach($res as $k=>$v){
//			$res[$k]['statusname']=$statusname[$v['status']];
//		}
		$design_list = M('design')->Field("id,name")->select();
		$design_list=convert_arr_key($design_list,'id');
//		show_bug($design_list);
		$this->assign('design_list',$design_list);//设计师

		$show = $Page->show();// 分页显示输出
		$this->assign('list',$res);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
	}
	public function designedit(){

		$act = I('GET.act','add');
		$this->assign('act',$act);
		$id = I('GET.id');
		$apoint_info = array();
		if($id){
			$apoint_info = D('apointment_design')->where('id='.$id)->find();
			$this->assign('apoint_info',$apoint_info);
		}

		$this->display();
	}
	public function addcomment(){

		$this->assign('act','add');
		$id = I('GET.id');
		$apoint_info = array();
		if($id){
			$apoint_info = D('design')->where('id='.$id)->find();
			$this->assign('apoint_info',$apoint_info);
		}
		$design_list = M('design')->Field("id,name")->select();
		$design_list=convert_arr_key($design_list,'id');
		$this->assign('design_list',$design_list);

		$this->display();
	}
	public function commentHandle(){
		$data = I('post.');
//		show_bug($data);
//		exit;
		if($data['act'] == 'add'){
			$data['is_check']=1;
			$data['add_time']=time();

			$d = D('comment_design')->add($data);
		}
		if($data['act'] == 'edit')
		{
			$d = D('comment_design')->where('comment_id='.$data['id'])->save(array('is_check'=>1));
		}
		if($data['act'] == 'del'){
			$r = D('comment_design')->where('comment_id='.$data['del_id'])->delete();
			if($r) exit(json_encode(1));
		}
		if($d !== false){
			$this->success("操作成功",U('Admin/Apointment/designlist'));
		}else{
			$this->error("操作失败",U('Admin/Apointment/designlist'));
		}
	}
	public function designHandle(){
		$data = I('post.');
		if($data['act'] == 'add'){
			$d = D('apointment_design')->add($data);
		}
		if($data['act'] == 'edit')
		{
			$d = D('apointment_design')->where("id=$data[id]")->save($data);
		}
		if($data['act'] == 'del'){
			$r = D('apointment_design')->where('id='.$data['del_id'])->delete();
			if($r) exit(json_encode(1));
		}
		if($d !== false){
			$this->success("操作成功",U('Admin/Apointment/designlist'));
		}else{
			$this->error("操作失败",U('Admin/Apointment/designlist'));
		}
	}
	public function categoryHandle(){
		$data = I('post.');
		if($data['act'] == 'add'){
			$d = D('apointment')->add($data);
		}
		if($data['act'] == 'edit')
		{
			$d = D('apointment')->where("id=$data[id]")->save($data);
		}
		if($data['act'] == 'del'){
			$r = D('apointment')->where('id='.$data['del_id'])->delete();
			if($r) exit(json_encode(1));
		}
		if($d !== false){
			$this->success("操作成功",U('Admin/Apointment/apointlist'));
		}else{
			$this->error("操作失败",U('Admin/Apointment/apointlist'));
		}
	}

    public function storeHandle(){
    	$data = I('post.');

    	if($data['act'] == 'del'){
    		$r = D('store')->where('id='.$data['id'])->delete();
    		if($r) exit(json_encode(1));
    	}
    	if($data['act'] == 'add'){
			$data['addtime']=time();
    		$r = D('store')->add($data);
    	}
//		show_bug($data);exit;
    	if($data['act'] == 'edit'){
    		$r = M('store')->where('id='.$data['id'])->save($data);

    	}

    	if($r){
    		$this->success("操作成功",U('Admin/Apointment/storelist'));
    	}else{
    		$this->error("操作失败",U('Admin/Apointment/storelist'));
    	}
    }

	/**
	 * 预约类型
	 */
	public function apointTypeList(){
		$model = M("apointtype");
		$count = $model->count();
		$Page  = new \Think\Page($count,10);
		$show  = $Page->show();
		$apointtypeList = $model->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('show',$show);
		$this->assign('apointtypeList',$apointtypeList);
		$this->display();
	}


	/**
	 * 添加修改编辑  预约类型
	 */

	public  function addEditapointType(){
		$_GET['id'] = $_GET['id'] ? $_GET['id'] : 0;
		$model = M("apointtype");
		if(IS_POST)
		{
			$model->create();
			if($_GET['id'])
				$model->save();
			else
				$model->add();

			$this->success("操作成功!!!",U('Admin/Apointment/apointTypeList'));
			exit;
		}
		$apointtype = $model->find($_GET['id']);
		$this->assign('apointtype',$apointtype);
		$this->display('_apointType');
	}

	/**
	 * 删除预约类型
	 */
	public function delapointType()
	{

		// 删除分类
		M('apointtype')->where("id = {$_GET['id']}")->delete();
		$this->success("操作成功!!!",U('Admin/Apointment/apointTypeList'));
	}


	public function get_goods(){
    	$prom_id = I('id');
    	$count = M('goods')->where("prom_id=$prom_id and prom_type=3")->count(); 
    	$Page  = new \Think\Page($count,10);
    	$goodsList = M('goods')->where("prom_id=$prom_id and prom_type=3")->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->assign('goodsList',$goodsList);
    	$this->display(); 
    }   
    
    public function search_goods(){
    	$GoodsLogic = new \Admin\Logic\GoodsLogic;
    	$brandList = $GoodsLogic->getSortBrands();
    	$this->assign('brandList',$brandList);
    	$categoryList = $GoodsLogic->getSortCategory();
    	$this->assign('categoryList',$categoryList);
    	
    	$goods_id = I('goods_id');
    	$where = ' is_on_sale = 1 and prom_type=0 and store_count>0 ';//搜索条件
    	if(!empty($goods_id)){
    		$where .= " and goods_id not in ($goods_id) ";
    	}
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));
    		$grandson_ids = getCatGrandson(I('cat_id'));
    		$where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
    	}
    	if(I('brand_id')){
    		$this->assign('brand_id',I('brand_id'));
    		$where = "$where and brand_id = ".I('brand_id');
    	}
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}
    	$count = M('goods')->where($where)->count();
    	$Page  = new \Think\Page($count,10);
    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$show = $Page->show();//分页显示输出
    	$this->assign('page',$show);//赋值分页输出
    	$this->assign('goodsList',$goodsList);
    	$tpl = I('get.tpl','search_goods');
    	$this->display($tpl);
    }
    

    private function initEditor()
    {
    	$this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'promotion')));
    	$this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'promotion')));
    	$this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'promotion')));
    	$this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'promotion')));
    	$this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'promotion')));
    	$this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'promotion')));
    	$this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'promotion')));
    	$this->assign("URL_Home", "");
    }
    
}