<?php

namespace Admin\Controller;
use Think\AjaxPage;
use Admin\Logic\GoodsLogic;

class SignController extends BaseController {

	public function setting(){
		$set=M('sign_set')->find();
		$act=I('act');
		$data['cycle']=I('cycle');
		$data['reward_default_first']=I('reward_default_first');
		$data['reward_default_day']=I('reward_default_day');
		$data['reword_order']=I('reword_order');
		$data['reword_sum']=I('reword_sum');
		if (!empty($data['reword_order'])) {
			$reword_order = array();
			foreach ($data['reword_order'] as $k1 => $v1 ) {
				foreach ($v1 as $k2 => $v2 ) {
					if (!empty($k1) && !empty($v2)) {
						$reword_order[$k2][$k1] = $v2;
					}
				}
			}
			$data['reword_order'] = serialize($reword_order);
		}
		if (!empty($data['reword_sum'])) {
			$reword_sum = array();
			foreach ($data['reword_sum'] as $k1 => $v1 ) {
				foreach ($v1 as $k2 => $v2 ) {
					if (!empty($k1) && !empty($v2)) {
						$reword_sum[$k2][$k1] = $v2;
					}
				}
			}
			$data['reword_sum'] = serialize($reword_sum);
		}
		if($act){
			if($set){
				M('sign_set')->where(array('id'=>$set['id']))->save($data);
			}else{
				M('sign_set')->add($data);
			}
			$this->success("操作成功!!!");
		}

//var_dump($set);
		$set['reword_order']=unserialize($set['reword_order']);
		$set['reword_sum']=unserialize($set['reword_sum']);

		$this->assign('setting',$set);
		$this->display();
	}

	public function tpl(){
		$this->assign('tpltype',$_GET['tpltype']);

		$this->display();
	}
    
}