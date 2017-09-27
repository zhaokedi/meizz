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
 * Date: 2015-12-11
 */
namespace Admin\Controller;
use Think\AjaxPage;

class RedbagController extends BaseController {
    /**----------------------------------------------*/
     /*                红包控制器                  */
    /**----------------------------------------------*/
    /*
     * 红包类型列表
     */
    public function index(){
        //获取红包列表

    	$count =  M('redbag')->count();
    	$Page = new \Think\Page($count,10);
        $show = $Page->show();
        $lists = M('redbag')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('redbags',C('REDBAG_TYPE'));
        $this->display();
    }

    /*
     * 添加编辑一个红包类型
     */
    public function redbag_info(){
        if(IS_POST){
        	$data = I('post.');
            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time'] = strtotime($data['send_end_time']);
//            $data['use_end_time'] = strtotime($data['use_end_time']);
//            $data['use_start_time'] = strtotime($data['use_start_time']);
            if($data['send_start_time'] > $data['send_end_time']){
                $this->error('发放日期填写有误');
            }
            if(empty($data['id'])){
            	$data['add_time'] = time();
            	$row = M('redbag')->add($data);
            }else{
            	$row =  M('redbag')->where(array('id'=>$data['id']))->save($data);
            }
            if(!$row)
                $this->error('编辑红包失败');
            $this->success('编辑红包成功',U('Admin/Redbag/index'));
            exit;
        }
        $cid = I('get.id');
        if($cid){
        	$redbag = M('redbag')->where(array('id'=>$cid))->find();
        	$this->assign('redbag',$redbag);
        }else{
        	$def['send_start_time'] = strtotime("+1 day");
        	$def['send_end_time'] = strtotime("+1 month");
//        	$def['use_start_time'] = strtotime("+1 day");
//        	$def['use_end_time'] = strtotime("+2 month");
        	$this->assign('redbag',$def);
        }
        $this->display();
    }

    /*
    * 红包发放
    */
    public function make_redbag(){
        //获取红包ID
        $cid = I('get.id');
        $type = I('get.type');
        //查询是否存在红包
        $data = M('redbag')->where(array('id'=>$cid))->find();
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    	if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("红包类型不存在");
        if($type != 4) $this->error("该红包类型不支持发放");
        if(IS_POST){
            $num  = I('post.num');
            if($num>$remain) $this->error($data['name'].'发放量不够了');
            if(!$num > 0) $this->error("发放数量不能小于0");
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            for($i=0;$i<$num; $i++){
                do{
                    $code = get_rand_str(8,0,1);//获取随机8位字符串
                    $check_exist = M('redbag_list')->where(array('code'=>$code))->find();
                }while($check_exist);
                $add['code'] = $code;
                M('redbag_list')->add($add);
            }
            M('redbag')->where("id=$cid")->setInc('send_num',$num);
            adminLog("发放".$num.'张'.$data['name']);
            $this->success("发放成功",U('Admin/Redbag/index'));
            exit;
        }
        $this->assign('redbag',$data);
        $this->display();
    }

    public function ajax_get_user(){
    	//搜索条件
    	$condition = array();
    	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    	I('email') ? $condition['email'] = I('email') : false;
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
    	$model = M('users');
    	$count = $model->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $user_level = M('user_level')->getField('level_id,level_name',true);
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$this->assign('page',$show);
    	$this->display();
    }

    public function send_redbag(){
    	$cid = I('cid');
    	if(IS_POST){
    		$level_id = I('level_id');
    		$user_id = I('user_id');
    		$insert = '';
    		$redbag = M('redbag')->where("id=$cid")->find();
    		if($redbag['createnum']>0){
    			$remain = $redbag['createnum'] - $redbag['send_num'];//剩余派发量
    			if($remain<=0) $this->error($redbag['name'].'已经发放完了');
    		}

    		if(empty($user_id) && $level_id>=0){
    			if($level_id==0){
    				$user = M('users')->where("is_lock=0")->select();
    			}else{
    				$user = M('users')->where("is_lock=0 and level_id=$level_id")->select();
    			}
    			if($user){
    				$able = count($user);//本次发送量
    				if($redbag['createnum']>0 && $remain<$able){
    					$this->error($redbag['name'].'派发量只剩'.$remain.'张');
    				}
    				foreach ($user as $k=>$val){
    					$user_id = $val['user_id'];
    					$time = time();
    					$gap = ($k+1) == $able ? '' : ',';
    					$insert .= "($cid,1,$user_id,$time)$gap";
    				}
    			}
    		}else{
    			$able = count($user_id);//本次发送量
    			if($redbag['createnum']>0 && $remain<$able){
    				$this->error($redbag['name'].'派发量只剩'.$remain.'张');
    			}
    			foreach ($user_id as $k=>$v){
    				$time = time();
    				$gap = ($k+1) == $able ? '' : ',';
    				$insert .= "($cid,1,$v,$time)$gap";
    			}
    		}
			$sql = "insert into __PREFIX__redbag_list (`cid`,`type`,`uid`,`send_time`) VALUES $insert";
			M()->execute($sql);
			M('redbag')->where("id=$cid")->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$redbag['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->select();
    	$this->assign('level',$level);
    	$this->assign('cid',$cid);
    	$this->display();
    }

    public function send_cancel(){

    }

    /*
     * 删除红包类型
     */
    public function del_redbag(){
        //获取红包ID
        $cid = I('get.id');
        //查询是否存在红包
        $row = M('redbag')->where(array('id'=>$cid))->delete();
        if($row){
            //删除此类型下的红包
            M('redbag_list')->where(array('cid'=>$cid))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }


    /*
     * 红包详细查看
     */
    public function redbag_list(){
        //获取红包ID
        $cid = I('get.id');
        //查询是否存在红包
        $check_redbag = M('redbag')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_redbag['id'] > 0)
            $this->error('不存在该类型红包');

        //查询该红包的列表的数量
        $sql = "SELECT count(1) as c FROM `__PREFIX__redbag_list` l ".
                "LEFT JOIN `__PREFIX__redbag` c ON c.id = l.cid ". //联合红包表查询名称
                "LEFT JOIN `__PREFIX__order` o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN `__PREFIX__users` u ON u.user_id = l.uid WHERE l.cid = ".$cid;    //联合用户表去查询用户名
        $count = M()->query($sql);
        $count = $count[0]['c'];
    	$Page = new \Think\Page($count,10);
    	$show = $Page->show();

        //查询该红包的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM `__PREFIX__redbag_list` l ".
                "LEFT JOIN `__PREFIX__redbag` c ON c.id = l.cid ". //联合红包表查询名称
                "LEFT JOIN `__PREFIX__order` o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN `__PREFIX__users` u ON u.user_id = l.uid WHERE l.cid = ".$cid.    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $redbag_list = M()->query($sql);
        $this->assign('redbag_type',C('REDBAG_TYPE'));
        $this->assign('type',$check_redbag['type']);
        $this->assign('lists',$redbag_list);
    	$this->assign('page',$show);
        $this->display();
    }

    /*
     * 删除一张红包
     */
    public function redbag_list_del(){
        //获取红包ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在红包
         $row = M('redbag_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }
}