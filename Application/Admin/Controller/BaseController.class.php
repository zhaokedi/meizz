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
use Think\Controller;
use Admin\Logic\UpgradeLogic;
class BaseController extends Controller {

    /**
     * 析构函数
     */
    function __construct()
    {
        parent::__construct();
        $upgradeLogic = new UpgradeLogic();
        $upgradeMsg = $upgradeLogic->checkVersion(); //升级包消息
        $this->assign('upgradeMsg',$upgradeMsg);
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin',$navigate_admin);
        tpversion();
   }

    /*
     * 初始化操作
     */
    public function _initialize()
    {
        $this->assign('action',ACTION_NAME);
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
        	//return;
        }else{
        	if(session('admin_id') > 0 ){
        	//	$this->check_priv();//检查管理员菜单操作权限
        	}else{
        		$this->error('请先登陆',U('Admin/Admin/login'),1);
        	}
        }
        $this->public_assign();
    }

    /**
     * 保存公告变量到 smarty中 比如 导航
     */
    public function public_assign()
    {
       $tpshop_config = array();
       $tp_config = M('config')->select();
       foreach($tp_config as $k => $v)
       {
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('tpshop_config', $tpshop_config);
    }

    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
		$act_list = session('act_list');
		$no_check = array('login','logout','vertifyHandle','vertify','imageUp','upload');
    	if($ctl == "Index" && $act == 'index'){
    		return true;
    	}elseif(strpos('ajax',$act) || in_array($act,$no_check) || $act_list == 'all'){
    		return true;
    	}else{
    		$mod_id = M('system_module')->where("ctl='$ctl' and act='$act'")->getField('mod_id');
    		$act_list = explode(',', $act_list);
    		if($mod_id){
    			if(!in_array($mod_id, $act_list)){
    				$this->error('您的账号没有此菜单操作权限,超级管理员可分配权限',U('Admin/Index/index'));
    				exit;
    			}else{
    				return true;
    			}
    		}else{
    			$this->error('请系统管理员先在菜单管理页添加该菜单',U('Admin/System/menu'));
    			exit;
    		}
    	}
    }
    public function get_access_token(){
        //判断是否过了缓存期
        $wechat = M('wx_user')->find();
        $expire_time = $wechat['web_expires'];
        if($expire_time > time()){
            return $wechat['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wechat[appid]}&secret={$wechat[appsecret]}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7000; // 提前200秒过期
        M('wx_user')->where(array('id'=>$wechat['id']))->save(array('web_access_token'=>$return['access_token'],'web_expires'=>$web_expires));
        return $return['access_token'];
    }
    final public function clear_html($array) {
        if (!is_array($array))
            return trim(htmlspecialchars($array, ENT_QUOTES));
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->clear_html($value);
            } else {
                $array[$key] = trim(htmlspecialchars($value, ENT_QUOTES));
            }
        }
        return $array;
    }
    public function oldUploadFile($filemulu ='images', $token = '') {
        $token = !empty($token) ? $token : date('Ymd');
        require THINK_PATH.'Library/Org/Util/UploadFile.class.php';
//        bpBase::loadOrg('UploadFile');
        $getupload_dir = "/Public/upload/" . $filemulu . "/" . $token . "/" . date('Ymd') . '/';
        if(defined('ABS_UPLOAD_PATH')) $getupload_dir=ABS_UPLOAD_PATH.$getupload_dir;
        $upload_dir = '.'.$getupload_dir;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $upload = new \UploadFile();
        $upload->maxSize = 10 * 1024 * 1024;
        $upload->allowExts = array('jpeg', 'jpg', 'png', 'mp3', 'gif', 'pem');
        //$upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif','application/octet-stream');
        $upload->savePath = $upload_dir;
        $upload->thumb = false;
        $upload->saveRule = 'uniqid';
        if ($upload->upload()) {
            $uploadList = $upload->getUploadFileInfo();
            return array('error' => 0, 'imgurl' => $getupload_dir, 'data' => $uploadList);
        } else {
            return array('error' => 1, 'imgurl' => $getupload_dir, 'data' => $upload->getErrorMsg());
        }
    }
}