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
 * Date: 2015-09-09
 */

namespace Admin\Controller;

use Think\Verify;

class AdminController extends BaseController {

    public function index(){
    	$res = $list = array();
    	$keywords = I('keywords');
    	if(empty($keywords)){
    		$res = D('admin')->select();
    	}else{
    		$res = D()->query("select * from __PREFIX__admin where user_name like '%$keywords%' order by admin_id");
    	}
    	$role = D('admin_role')->getField('role_id,role_name');
    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
        $this->display();
    }

    public function admin_info(){
    	$admin_id = I('get.admin_id',0);
    	if($admin_id){
    		$info = D('admin')->where("admin_id=$admin_id")->find();
                $info['password'] =  "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
    	$role = D('admin_role')->where('1=1')->select();
    	$this->assign('role',$role);
    	$this->display();
    }

    public function adminHandle(){
    	$data = I('post.');
    	if(empty($data['password'])){
    		unset($data['password']);
    	}else{
    		$data['password'] = encrypt($data['password']);
    	}
    	if($data['act'] == 'add'){
    		unset($data['admin_id']);
    		$data['add_time'] = time();
    		if(D('admin')->where("user_name='".$data['user_name']."'")->count()){
    			$this->error("此用户名已被注册，请更换",U('Admin/Admin/admin_info'));
    		}else{
    			$r = D('admin')->add($data);
    		}
    	}

    	if($data['act'] == 'edit'){
    		$r = D('admin')->where('admin_id='.$data['admin_id'])->save($data);
    	}

        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = D('admin')->where('admin_id='.$data['admin_id'])->delete();
    		exit(json_encode(1));
    	}

    	if($r){
    		$this->success("操作成功",U('Admin/Admin/index'));
    	}else{
    		$this->error("操作失败",U('Admin/Admin/index'));
    	}
    }


    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?admin_id') && session('admin_id')>0){
             $this->error("您已登录",U('Admin/Index/index'));
        }

        if(IS_POST){
            $verify = new Verify();
			if(!tpCache('verify_code.admin_show')){
				if (!$verify->check(I('post.vertify'), "Admin/Login")) {
					exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
				}
			}
            $condition['user_name'] = I('post.username');
            $condition['password'] = I('post.password');
            if(!empty($condition['user_name']) && !empty($condition['password'])){
                $condition['password'] = encrypt($condition['password']);
               	$admin_info = M('admin')->join('__ADMIN_ROLE__ ON __ADMIN__.role_id=__ADMIN_ROLE__.role_id')->where($condition)->find();
                if(is_array($admin_info)){
                    session('admin_id',$admin_info['admin_id']);
                    session('act_list',$admin_info['act_list']);
                    $last_login_time = M('admin_log')->where("admin_id = ".$admin_info['admin_id']." and log_info = '后台登录'")->order('log_id desc')->limit(1)->getField('log_time');
                    M('admin')->where("admin_id = ".$admin_info['admin_id'])->save(array('last_login'=>time(),'last_ip'=>  getIP()));
                    session('last_login_time',$last_login_time);
                    adminLog('后台登录',__ACTION__);
                    $url = session('from_url') ? session('from_url') : U('Admin/Index/index');
                    exit(json_encode(array('status'=>1,'url'=>$url)));
                }else{
                    exit(json_encode(array('status'=>0,'msg'=>'账号密码不正确')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }

        $this->display();
    }

    /**
     * 退出登陆
     */
    public function logout(){
        session_unset();
        session_destroy();
        $this->success("退出成功",U('Admin/Admin/login'));
    }

    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry("Admin/Login");
    }

    public function role(){
    	$list = D('admin_role')->order('role_id desc')->select();
    	$this->assign('list',$list);
    	$this->display();
    }

    public function role_info(){
    	$role_id = I('get.role_id');
    	$tree = $detail = array();
    	if($role_id){
    		$detail = D('admin_role')->where("role_id=$role_id")->find();
    		$this->assign('detail',$detail);
    	}

    	$res = D('system_module')->order('mod_id ASC')->select();
    	if($res){
    		foreach($res as $k=>$v){
    			if($detail['act_list']){
    				$act_list = explode(',', $detail['act_list']);
    				$v['enable'] = in_array($v['mod_id'], $act_list) ? 1 : 0;
    			}else{
    				$v['enable'] = 0 ;
    			}
    			$modules[$v['mod_id']] = $v;
    		}

    		if($modules){
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'top'){
    					$tree[$k] = $v;
    				}
    			}
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'menu'){
    					$tree[$v['parent_id']]['menu'][$k] = $v;
    				}
    			}
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'module'){
    					$ppk = $modules[$v['parent_id']]['parent_id'];
    					$tree[$ppk]['menu'][$v['parent_id']]['menu'][$k] = $v;
    				}
    			}
    		}
    	}

    	$this->assign('menu_tree',$tree);
    	$this->display();
    }

    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['menu']) ? implode(',', $data['menu']) : '';
    	if(empty($data['role_id'])){
    		$r = D('admin_role')->add($res);
    	}else{
    		$r = D('admin_role')->where('role_id='.$data['role_id'])->save($res);
    	}
		if($r){
			adminLog('管理角色',__ACTION__);
			$this->success("操作成功!",U('Admin/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->success("操作失败!",U('Admin/Admin/role'));
		}
    }

    public function roleDel(){
    	$role_id = I('post.role_id');
    	$admin = D('admin')->where('role_id='.$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("role_id=$role_id")->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }

    public function supplyDel(){
        $id = I('post.id');
        $d = M('supply')->where("id=$id")->delete();
        if($d){
            exit(json_encode(1));
        }else{
            exit(json_encode("删除失败"));
        }
    }

    public function log(){
    	$Log = M('admin_log');
    	$p = I('p',1);
    	$logs = $Log->join('__ADMIN__ ON __ADMIN__.admin_id =__ADMIN_LOG__.admin_id')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = $Log->where('1=1')->count();
    	$Page = new \Think\Page($count,20);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->display();
    }

    public function supply_info(){
        $id = I('get.id',0);
        if($id){
            $info = D('supply')->where("id=$id")->find();
            $this->assign('info',$info);
        }
        $act = empty($id) ? 'add' : 'edit';
        $this->assign('act',$act);
        $sup = D('supply')->where('1=1')->select();
        $this->assign('sup',$sup);
        $this->display();
    }

    public function supply(){
        $res = array();
        $res = D('supply')->select();
        $this->assign('res',$res);
        $this->display();
    }

    public function supplyHandle(){
        $data = I('post.');
        if(D('supply')->where("sup_name='".$data['sup_name']."'")->count()){
            $this->error("此供应商已存在，请更换",U('Admin/Admin/supply_info'));
        }else{
            unset($data['id']);
            $r = D('supply')->add($data);
        }

        if($data['act'] == 'edit'){
            $r = D('supply')->where('id='.$data['id'])->save($data);
        }

        if($data['act'] == 'del' && $data['id']>1){
            $r = D('supply')->where('id='.$data['id'])->delete();
            exit(json_encode(1));
        }

        if($r){
            $this->success("操作成功",U('Admin/Admin/supply'));
        }else{
            $this->error("操作失败",U('Admin/Admin/supply_info'));
        }
        die;
    }

    public function op(){
        $type = I('post.type');
        $selected_id = I('post.selected');
        // var_dump($_POST);die;
        if(!in_array($type,array('del','show','hide')) || !$selected_id)
            $this->error('非法操作');
        $where = "log_id IN ({$selected_id})";
        if($type == 'del'){
            //删除日志
            // $where .= " OR parent_id IN ({$selected_id})";
            // var_dump($where);die;
            $row = M('admin_log')->where($where)->delete();
//            exit(M()->getLastSql());
        }
        // if($type == 'show'){
        //     $row = M('comment')->where($where)->save(array('is_show'=>1));
        // }
        // if($type == 'hide'){
        //     $row = M('comment')->where($where)->save(array('is_show'=>0));
        // }
        if(!$row)
            $this->error('操作失败');
        $this->success('操作成功');

    }
}