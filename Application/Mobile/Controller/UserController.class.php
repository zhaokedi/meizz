<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 2015-11-21
 */
namespace Mobile\Controller;
use Home\Logic\UsersLogic;
use Think\Page;
use Think\Verify;

class UserController extends MobileBaseController {
    public $cartLogic;
    public $user_id = 0;
    public $user = array();
        /*
        * 初始化操作
        */
    public function _initialize() {
        parent::_initialize();
        $this->cartLogic = new \Home\Logic\CartLogic();
        if(session('?User'))
        {
        	$user = session('User');
                $user = M('users')->where("user_id = {$user['user_id']}")->find();
                session('User',$user);  //覆盖session 中的 User
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('User',$user); //存储用户信息

        }
        $nologin = array(
        	'login','pop_login','do_login','logout','verify','set_pwd','finished',
        	'verifyHandle','reg','send_sms_reg_code','find_pwd','check_validate_code',
        	'forget_pwd','check_captcha','check_username','send_validate_code','express',
        );
        if(!$this->user_id && !in_array(ACTION_NAME,$nologin)){
        	header("location:".U('Mobile/User/login'));
        	exit;
        }

        $order_status_coment = array(
            'WAITPAY'=>'待付款 ', //订单查询状态 待支付
            'WAITSEND'=>'待发货', //订单查询状态 待发货
            'WAITRECEIVE'=>'待收货', //订单查询状态 待收货
            'WAITCCOMMENT'=>'待评价', //订单查询状态 待评价
        );
        $this->assign('order_status_coment',$order_status_coment);
    }
    public function research(){

        $research_list=M('research_list')->where('is_star =1')->limit(1)->find();
        $id=I('id');
        if($id){
            $where['pid']=$id;
        }else{
            $where['pid']=$research_list['id'];
        }
        $where['enabled']=1;

        $this->assign('research_list',$research_list);
        $question=M('research')->where($where)->order('orderby desc')->select();
        foreach($question as $k=>$v){
            $question[$k]['option']=unserialize($v['option']);
        }
        if(IS_AJAX){
            if(!$this->user_id){
                $this->ajaxReturn(array('status'=>-1,'msg'=>"请先登录"));
            }
            $research_count=M('research_log')->where(array('user_id'=>$this->user_id,'pid'=>$where['pid']))->count();
            if($research_count>0){
                $this->ajaxReturn(array('status'=>-1,'msg'=>"您已经参与过调查，请务重复提交"));
            }
            $ids=I('ids');
            $sort=I('sort');
            $data2['user_id']=$this->user_id;
            $data2['pid']=$where['pid'];
            foreach($ids as $k=>$v){
                $qs=M('research')->find($v);
                $option=unserialize($qs['option']);
                foreach($option as $k1=>$v1){
                    if($sort[$k]==$v1['sort']){
                        $num=$v1['count']+1;
                        $option[$k1]['count']=$num;
                    }
                }
                $data['sum']=array("exp",'sum+1');
                $data['option']= serialize($option);
                M('research')->where(array("id"=>$v))->save( $data);

            }
            M('research_log')->add($data2);
            $this->ajaxReturn(array('status'=>1,'msg'=>"投票成功"));
        }
        $this->assign('question',$question);
        $this->display("Index/research");
    }
    /*
     * 用户中心首页
     */
    public function index(){

        $order_count = M('order')->where("user_id = {$this->user_id}")->count(); // 我的订单数
        $goods_collect_count = M('goods_collect')->where("user_id = {$this->user_id}")->count(); // 我的商品收藏
        $comment_count = M('comment')->where("user_id = {$this->user_id}")->count();//  我的评论数
        $coupon_count = M('cashier_wxcoupon_receive')->where("user_id = {$this->user_id}")->count(); // 我的优惠券数量
        $level_name = M('user_level')->where("level_id = {$this->user['level']}")->getField('level_name'); // 等级名称
        $this->assign('level_name',$level_name);
        $this->assign('order_count',$order_count);
        $this->assign('goods_collect_count',$goods_collect_count);
        $this->assign('comment_count',$comment_count);
        $this->assign('coupon_count',$coupon_count);
        $this->display();
    }


    public function logout(){
        session_unset();
        session_destroy();
        setcookie('cn','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        //$this->success("退出成功",U('Mobile/Index/index'));
        header("Location:".U('Mobile/Index/index'));
    }

    /*
     * 账户资金
     */
    public function account(){
        $user = session('User');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id,I('get.type'));
        $account_log = $data['result'];

        $this->assign('user',$user);
        $this->assign('account_log',$account_log);
        $this->assign('page',$data['show']);

        if($_GET['is_ajax'])
        {
            $this->display('ajax_account_list');
            exit;
        }
        $this->display();
    }

    public function coupon(){
        //
        $logic = new UsersLogic();
        $data = $logic->get_coupon($this->user_id,$_REQUEST['type']);
        $coupon_list = $data['result'];
        $this->assign('coupon_list',$coupon_list);
        $this->assign('page',$data['show']);
        if($_GET['is_ajax'])
        {
            $this->display('ajax_coupon_list');
            exit;
        }
        $this->display();
    }
    /**
     *  登录
     */
    public function login(){
        if($this->user_id > 0){
        	header("Location: ".U('Mobile/User/Index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Mobile/User/index");
        $this->assign('referurl',$referurl);
        $this->display();
    }


    public function do_login(){
    	$username = I('post.username');
    	$password = I('post.password');
    	$username = trim($username);
    	$password = trim($password);
    	$logic = new UsersLogic();
    	$res = $logic->login($username,$password);
    	if($res['status'] == 1){
    		$res['url'] =  urldecode(I('post.referurl'));
    		session('User',$res['result']);
    		setcookie('user_id',$res['result']['user_id'],null,'/');
    		setcookie('is_distribut',$res['result']['is_distribut'],null,'/');
    		$nickname = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
    		setcookie('uname',$nickname,null,'/');
    		$cartLogic = new \Home\Logic\CartLogic();
    		$cartLogic->login_cart_handle($this->session_id,$res['result']['user_id']);  //用户登录后 需要对购物车 一些操作
    	}
    	exit(json_encode($res));
    }

    /**
     *  注册
     */
    public function reg(){
        $invite=$_GET['invite'];
        $this->assign('invite', $invite);
        if(IS_POST){
            $logic = new UsersLogic();
            //验证码检验
            //$this->verifyHandle('user_reg');
            $username = I('post.username','');
            $invite_id=intval(I('post.invite_id',''));
            $password = I('post.password','');
            $password2 = I('post.password2','');
            //是否开启注册验证码机制

            if(check_mobile($username) && tpCache('sms.regis_sms_enable')){
                $code = I('post.mobile_code','');

                if(!$code)
                    $this->error('请输入验证码');
                $check_code = $logic->sms_code_verify($username,$code,$this->session_id);
                if($check_code['status'] != 1)
                    $this->error($check_code['msg']);

            }

            $data = $logic->reg($username,$password,$password2,'',$invite_id);
            if($data['status'] != 1)
                $this->error($data['msg']);
            session('User',$data['result']);
            setcookie('user_id',$data['result']['user_id'],null,'/');
            setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
            $cartLogic = new \Home\Logic\CartLogic();
            $cartLogic->login_cart_handle($this->session_id,$data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
            $this->success($data['msg'],U('Mobile/User/index'));
            exit;
        }
        $this->assign('regis_sms_enable',tpCache('sms.regis_sms_enable')); // 注册启用短信：
        $this->assign('sms_time_out',tpCache('sms.sms_time_out')); // 手机短信超时时间
        $this->display();
    }

    /*
     * 订单列表
     */
    public function order_list()
    {
        $where = 'is_integral=0 and user_id='.$this->user_id;
        //条件搜索
        if(in_array(strtoupper(I('type')), array('WAITCCOMMENT','COMMENTED')))
        {
           $where .= " AND order_status in(1,4) "; //代评价 和 已评价
        }
        elseif(I('type'))
       {
           $where .= C(strtoupper(I('type')));
       }
        $count = M('order')->where($where)->count();
        $Page = new Page($count,10);

        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        foreach($order_list as $k=>$v)
        {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
//            show_bug($v);
        }


        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('page',$show);
        $this->assign('lists',$order_list);
        $this->assign('active','order_list');
        $this->assign('active_status',I('get.type'));
        if($_GET['is_ajax'])
        {
            $this->display('ajax_order_list');
            exit;
        }
        $this->display();
    }
    public function order_list1()
    {
        $where = ' user_id='.$this->user_id;
        //条件搜索
        if(in_array(strtoupper(I('type')), array('WAITCCOMMENT','COMMENTED')))
        {
            $where .= " AND order_status in(1,4) "; //代评价 和 已评价
        }
        elseif(I('type'))
        {
            $where .= C(strtoupper(I('type')));
        }
        $count = M('order')->where($where)->count();
        $Page = new Page($count,10);

        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        $statusname=C('ORDER_STATUS');
        foreach($order_list as $k=>$v)
        {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
            $order_list[$k]['orderstatusname'] = $statusname[$v['order_status']];

        }


        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('page',$show);
        $this->assign('lists',$order_list);
        $this->assign('active','order_list');
        $this->assign('active_status',I('get.type'));
        if($_GET['is_ajax'])
        {
            $this->display('ajax_order_list');
            exit;
        }
        $this->display();
    }


    /*
     * 订单列表
     */
    public function ajax_order_list(){

    }

    /*
     * 订单详情
     */
    public function order_detail(){
        $id = I('get.id');
        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        if(!$order_info){
            $this->error('没有获取到订单信息');
            exit;
        }
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        //$order_info['total_fee'] = $order_info['goods_price'] + $order_info['shipping_price'] - $order_info['integral_money'] -$order_info['coupon_price'] - $order_info['discount'];

        $region_list = get_region_list();
        $invoice_no = M('DeliveryDoc')->where("order_id = $id")->getField('invoice_no',true);
        $order_info[invoice_no] = implode(' , ', $invoice_no);
        //获取订单操作记录
        $order_action = M('order_action')->where(array('order_id'=>$id))->select();
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('region_list',$region_list);
        $this->assign('order_info',$order_info);

        $this->assign('order_action',$order_action);
        $this->display();
    }
    public function order_detailmm(){
        $id = I('get.id');
        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        if(!$order_info){
            $this->error('没有获取到订单信息');
            exit;
        }
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        //$order_info['total_fee'] = $order_info['goods_price'] + $order_info['shipping_price'] - $order_info['integral_money'] -$order_info['coupon_price'] - $order_info['discount'];

        $region_list = get_region_list();
        $invoice_no = M('DeliveryDoc')->where("order_id = $id")->getField('invoice_no',true);
        $order_info[invoice_no] = implode(' , ', $invoice_no);
        //获取订单操作记录
        $order_action = M('order_action')->where(array('order_id'=>$id))->select();
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('region_list',$region_list);
        $this->assign('order_info',$order_info);
        $this->assign('order_action',$order_action);
        $this->display();
    }
    public function express(){
    	$order_id = I('get.order_id',195);
    	$result = $order_goods = $delivery = array();
    	$order_goods = M('order_goods')->where("order_id=$order_id")->select();
    	$delivery = M('delivery_doc')->where("order_id=$order_id")->limit(1)->find();
		if($delivery['shipping_code'] && $delivery['invoice_no']){
			$result = queryExpress($delivery['shipping_code'],$delivery['invoice_no']);
			$this->assign('result',$result);
			$this->assign('order_goods',$order_goods);
			$this->assign('delivery',$delivery);
		}
    	$this->display();
    }

    /*
     * 取消订单
     */
    public function cancel_order(){
        $id = I('get.id');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id,$id);
        if($data['status'] < 0)
            $this->error($data['msg']);
        $this->success($data['msg']);
    }

    /*
     * 用户地址列表
     */
    public function address_list(){
        $address_lists = get_user_address_list($this->user_id);
        $region_list = get_region_list();
        $this->assign('region_list',$region_list);
        $this->assign('lists',$address_lists);
        $this->display();
    }
    /*
     * 添加地址
     */
    public function add_address()
    {
        if(IS_POST)
        {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id,0,I('post.'));
            if($data['status'] != 1)
                $this->error($data['msg']);
            elseif($_POST['source'] == 'cart2')
            {
               header ('Location:'.U('/Mobile/Cart/cart2',array('address_id'=>$data['result'])));
               exit;
            }

            $this->success($data['msg'],U('/Mobile/User/address_list'));
            exit();
        }
        $p = M('region')->where(array('parent_id'=>0,'level'=> 1))->select();
        $this->assign('province',$p);
        //$this->display('edit_address');
        $this->display();

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('id');
        $address = M('user_address')->where(array('address_id'=>$id,'user_id'=> $this->user_id))->find();
        if(IS_POST)
        {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id,$id,I('post.'));
            if($_POST['source'] == 'cart2'){
                header ('Location:'.U('/Mobile/Cart/cart2',array('address_id'=>$id)));
                exit;
            }
            else
                $this->success($data['msg'],U('/Mobile/User/address_list'));
            exit();
        }
        //获取省份
        $p = M('region')->where(array('parent_id'=>0,'level'=> 1))->select();
        $c = M('region')->where(array('parent_id'=>$address['province'],'level'=> 2))->select();
        $d = M('region')->where(array('parent_id'=>$address['city'],'level'=> 3))->select();
        if($address['twon']){
        	$e = M('region')->where(array('parent_id'=>$address['district'],'level'=>4))->select();
        	$this->assign('twon',$e);
        }

        $this->assign('province',$p);
        $this->assign('city',$c);
        $this->assign('district',$d);

        $this->assign('address',$address);
        $this->display();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default(){
        $id = I('get.id');
        $source = I('get.source');
        M('user_address')->where(array('user_id'=>$this->user_id))->save(array('is_default'=>0));
        $row = M('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->save(array('is_default'=>1));
        if($source == 'cart2')
        {
            header("Location:".U('Mobile/Cart/cart2'));
            exit;
        }elseif($source == 'integral'){
            header("Location:".U('Mobile/User/integral_submit'));
            exit;
        }
        else{
            header("Location:".U('Mobile/User/address_list'));
        }
    }

    /*
     * 地址删除
     */
    public function del_address(){
        $id = I('get.id');

        $address = M('user_address')->where("address_id = $id")->find();
        $row = M('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if($address['is_default'] == 1)
        {
            $address = M('user_address')->where("user_id = {$this->user_id}")->find();
            M('user_address')->where("address_id = {$address['address_id']}")->save(array('is_default'=>1));
        }

        if(!$row)
            $this->error('操作失败',U('/Mobile/User/address_list'));
        else
            $this->success("操作成功",U('/Mobile/User/address_list'));
    }

    /*
     * 评论晒单
     */
    public function comment(){
    	$user_id = $this->user_id;
    	$status = I('get.status');
    	$logic = new UsersLogic();
    	$result = $logic->get_comment($user_id,$status); //获取评论列表
    	$this->assign('comment_list',$result['result']);
        if($_GET['is_ajax'])
        {
            $this->display('ajax_comment_list');
            exit;
        }
    	$this->display();
    }
    public function comment1(){
        $user_id = $this->user_id;
        $status = I('get.status');
        $logic = new UsersLogic();
        $result = $logic->get_comment($user_id,$status); //获取评论列表
        $this->assign('comment_list',$result['result']);
        if($_GET['is_ajax'])
        {
            $this->display('ajax_comment_list');
            exit;
        }
        $this->display();
    }
    /*
     *添加评论
     */
    public function add_comment(){
    	if(IS_POST){
    		// 晒图片
    		if($_FILES[comment_img_file][tmp_name][0])
    		{
    			$upload = new \Think\Upload();// 实例化上传类
    			$upload->maxSize   =    $map['author'] = (1024*1024*3);// 设置附件上传大小 管理员10M  否则 3M
    			$upload->exts      =    array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    			$upload->rootPath  =    './Public/upload/comment/'; // 设置附件上传根目录
    			$upload->replace   =    true; // 存在同名文件是否是覆盖，默认为false
    			//$upload->saveName  =  'file_'.$id; // 存在同名文件是否是覆盖，默认为false
    			// 上传文件
    			$upinfo  =  $upload->upload();
    			if(!$upinfo) {// 上传错误提示错误信息
    				$this->error($upload->getError());
    			}else{
    				foreach($upinfo as $key => $val)
    				{
    					$comment_img[] = '/Public/upload/comment/'.$val['savepath'].$val['savename'];
    				}
    				$add['img'] = serialize($comment_img); // 上传的图片文件
    			}
    		}

    		$user_info = session('User');
    		$logic = new UsersLogic();
    		$add['goods_id'] = I('goods_id');
    		$add['email'] = $user_info['email'];
    		$hide_username = I('hide_username');
    		if(empty($hide_username)){
    			$add['username'] = $user_info['nickname'];
    		}
    		$add['order_id'] = I('order_id');
    		$add['service_rank'] = I('service_rank');
    		$add['deliver_rank'] = I('deliver_rank');
    		$add['goods_rank'] = I('goods_rank');
    		//$add['content'] = htmlspecialchars(I('post.content'));
    		$add['content'] = I('content');
    		$add['add_time'] = time();
    		$add['ip_address'] = getIP();
    		$add['user_id'] = $this->user_id;

    		//添加评论
    		$row = $logic->add_comment($add);
    		if($row[status] == 1)
    		{
    			$this->success('评论成功',U('/Mobile/Goods/goodsInfo',array('id'=>$add['goods_id'])));
    			exit();
    		}
    		else
    		{
    			$this->error($row[msg]);
    		}
    	}
        $rec_id = I('rec_id');
        $order_goods = M('order_goods')->where("rec_id = $rec_id")->find();
//        show_bug($order_goods);
        $this->assign('order_goods',$order_goods);
        $this->display();
    }

    /*
     * 个人信息
     */
    public function userinfo(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(IS_POST){
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : false;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            I('post.email') ? $post['email'] = I('post.email') : false; //邮箱
            I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //手机

            $c = M('users')->where("email = '{$post['email']}' and user_id != {$this->user_id}")->count();
            $c && $this->error("邮箱已被使用");

            $c = M('users')->where("mobile = '{$post['mobile']}' and user_id != {$this->user_id}")->count();
            $c && $this->error("手机已被使用");

            if(!$userLogic->update_info($this->user_id,$post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取订单城市
        $city =  M('region')->where(array('parent_id'=>$user_info['province'],'level'=>2))->select();
        //  获取订单地区
        $area =  M('region')->where(array('parent_id'=>$user_info['city'],'level'=>3))->select();
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('User',$user_info);
        $this->assign('sex',C('SEX'));
        $this->display();
    }
    public function changenick(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(IS_POST){
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : false;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            I('post.email') ? $post['email'] = I('post.email') : false; //邮箱
            I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //手机

            $c = M('users')->where("nickname = '{$post['nickname']}' and user_id != {$this->user_id}")->count();
            $c && $this->error("昵称已被使用");

//            $c = M('users')->where("mobile = '{$post['mobile']}' and user_id != {$this->user_id}")->count();
//            $c && $this->error("手机已被使用");

            if(!$userLogic->update_info($this->user_id,$post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取订单城市
        $city =  M('region')->where(array('parent_id'=>$user_info['province'],'level'=>2))->select();
        //  获取订单地区
        $area =  M('region')->where(array('parent_id'=>$user_info['city'],'level'=>3))->select();
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('User',$user_info);
        $this->assign('sex',C('SEX'));
        $this->display();
    }
    public function changename(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(IS_POST){
            I('post.name') ? $post['name'] = I('post.name') : false; //昵称
            if(!$userLogic->update_info($this->user_id,$post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        $this->assign('User',$user_info);
        $this->display();
    }
    public function changemobile(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(IS_POST){
            I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //昵称
            if(!$userLogic->update_info($this->user_id,$post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        $this->assign('User',$user_info);
        $this->display();
    }
    /*
     * 邮箱验证
     */
    public function email_validate(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step',1);
        //验证是否未绑定过
        if($user_info['email_validated'] == 0)
            $step = 2;
        //原邮箱验证是否通过
        if($user_info['email_validated'] == 1 && session('email_step1') == 1)
            $step = 2;
        if($user_info['email_validated'] == 1 && session('email_step1') != 1)
            $step = 1;
        if(IS_POST){
            $email = I('post.email');
            $code = I('post.code');
            $info = session('email_code');
            if(!$info)
                $this->error('非法操作');
            if($info['email'] == $email || $info['code'] == $code){
                if($user_info['email_validated'] == 0 || session('email_step1') == 1){
                    session('email_code',null);
                    session('email_step1',null);
                    if(!$userLogic->update_email_mobile($email,$this->user_id))
                        $this->error('邮箱已存在');
                    $this->success('绑定成功',U('Home/User/index'));
                }else{
                    session('email_code',null);
                    session('email_step1',1);
                    redirect(U('Home/User/email_validate',array('step'=>2)));
                }
                exit;
            }
            $this->error('验证码邮箱不匹配');
        }
        $this->assign('step',$step);
        $this->display();
    }

    /*
    * 手机验证
    */
    public function mobile_validate(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step',1);
        //验证是否未绑定过
        if($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if(IS_POST){
            $mobile = I('post.mobile');
            $code = I('post.code');
            $info = session('mobile_code');
            if(!$info)
                $this->error('非法操作');
            if($info['email'] == $mobile || $info['code'] == $code){
                if($user_info['email_validated'] == 0 || session('email_step1') == 1){
                    session('mobile_code',null);
                    session('mobile_step1',null);
                    if(!$userLogic->update_email_mobile($mobile,$this->user_id,2))
                        $this->error('手机已存在');
                    $this->success('绑定成功',U('Home/User/index'));
                }else{
                    session('mobile_code',null);
                    session('email_step1',1);
                    redirect(U('Home/User/mobile_validate',array('step'=>2)));
                }
                exit;
            }
            $this->error('验证码手机不匹配');
        }
        $this->assign('step',$step);
        $this->display();
    }

    public function collect_list(){
    	$userLogic = new UsersLogic();
    	$data = $userLogic->get_goods_collect($this->user_id);
    	$this->assign('page',$data['show']);// 赋值分页输出
    	$this->assign('goods_list',$data['result']);
        if($_GET['is_ajax'])
        {
            $this->display('ajax_collect_list');
            exit;
        }
    	$this->display();
    }

    /*
     *取消收藏
     */
    public function cancel_collect(){
       $collect_id = I('collect_id');
       $user_id = $this->user_id;
       if(M('goods_collect')->where("collect_id = $collect_id and user_id = $user_id")->delete()){
       		$this->success("取消收藏成功",U('User/collect_list'));
       }else{
       		$this->error("取消收藏失败",U('User/collect_list'));
       }
    }

    public function message_list()
    {
    	C('TOKEN_ON',true);
    	if(IS_POST)
    	{
                $this->verifyHandle('message');

    		$data = I('post.');
    		$data['user_id'] = $this->user_id;
    		$user = session('User');
    		$data['user_name'] = $user['nickname'];
    		$data['msg_time'] = time();
    		if(M('feedback')->add($data)){
    			$this->success("留言成功",U('User/message_list'));
                        exit;
    		}else{
    			$this->error('留言失败',U('User/message_list'));
                        exit;
    		}
    	}
    	$msg_type = array(0=>'商品咨询',1=>'库存及配送',2=>'支付问题',3=>'发票及保修',4=>'促销及赠品');
    	$count = M('feedback')->where("user_id=".$this->user_id)->count();
    	$Page = new Page($count,100);
    	$Page->rollPage = 2;
    	$message = M('feedback')->where("user_id=".$this->user_id)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach($message as $k=>$v){
            $map['parent_id']=$v['msg_id'];
            $result=M('feedback')->where($map)->select();
            $message[$k]['admin']=$result;
        }
//        show_bug($message);
    	$showpage = $Page->show();
    	header("Content-type:text/html;charset=utf-8");
    	$this->assign('page',$showpage);
    	$this->assign('message',$message);
    	$this->assign('msg_type',$msg_type);
    	$this->display();
    }

    public function points(){
        $count = M('account_log')->where("user_id=".$this->user_id)->count();
        $Page = new Page($count,16);
    	$account_log = M('account_log')->where("user_id=".$this->user_id)->order('log_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $showpage = $Page->show();

    	$this->assign('account_log',$account_log);
        $this->assign('page',$showpage);
        if($_GET['is_ajax'])
        {
            $this->display('ajax_points');
            exit;
        }
    	$this->display();
    }
    /*
     * 密码修改
     */
    public function password(){
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if($user['mobile'] == ''&& $user['email'] == '')
            $this->error('请先到电脑端绑定手机',U('/Mobile/User/index'));
        if(IS_POST){
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id,I('post.old_password'),I('post.new_password'),I('post.confirm_password')); // 获取用户信息
            if($data['status'] == -1)
                $this->error($data['msg']);
            $this->success($data['msg']);
            exit;
        }
        $this->display();
    }

    function forget_pwd(){
        if($this->user_id > 0){
    		header("Location: ".U('User/Index'));
    	}
    	$username = I('username');
    	if(IS_POST){
    		if(!empty($username)){
    			$this->verifyHandle('forget');
    			$field = 'mobile';
    			if(check_email($username)){
    				$field = 'email';
    			}
    			$user = M('users')->where("email='$username' or mobile='$username'")->find();
    			if($user){
    				session('find_password',array('user_id' => $user['user_id'],'username' =>$username,
    				'email' => $user['email'],'mobile' => $user['mobile'],'type'=>$field));
    				header("Location: ".U('User/find_pwd'));
    				exit;
    			}else{
    				$this->error("用户名不存在，请检查");
    			}
    		}
    	}
    	$this->display();
    }

    function find_pwd(){
    	if($this->user_id > 0){
    		header("Location: ".U('User/Index'));
    	}
    	$user = session('find_password');
    	if(empty($user)){
    		$this->error("请先验证用户名",U('User/forget_pwd'));
    	}
    	$this->assign('User',$user);
    	$this->display();
    }


    public function set_pwd(){
    	if($this->user_id > 0){
    		header("Location: ".U('User/Index'));
    	}
    	$check = session('validate_code');
    	if(empty($check)){
    		header("Location:".U('User/forget_pwd'));
    	}elseif($check['is_check']==0){
    		$this->error('验证码还未验证通过',U('User/forget_pwd'));
    	}
    	if(IS_POST){
    		$password = I('post.password');
    		$password2 = I('post.password2');
    		if($password2 != $password){
    			$this->error('两次密码不一致',U('User/forget_pwd'));
    		}
    		if($check['is_check']==1){
    			//$User = get_user_info($check['sender'],1);
                        $user = M('users')->where("mobile = '{$check['sender']}' or email = '{$check['sender']}'")->find();
    			M('users')->where("user_id=".$user['user_id'])->save(array('password'=>encrypt($password)));
    			session('validate_code',null);
    			//header("Location:".U('User/set_pwd',array('is_set'=>1)));
                        $this->success('新密码已设置行牢记新密码',U('User/index'));
                        exit;
    		}else{
    			$this->error('验证码还未验证通过',U('User/forget_pwd'));
    		}
    	}
    	$is_set = I('is_set',0);
    	$this->assign('is_set',$is_set);
    	$this->display();
    }

    //发送验证码
    public function send_validate_code(){
        $type = I('type');
        $send = I('send');

        $logic = new UsersLogic();
        $logic->send_validate_code($send, $type);
    }

    public function check_validate_code(){
    	$code = I('post.code');
    	$send = I('send');
    	$logic = new UsersLogic();
    	$logic->check_validate_code($code, $send);
    }

    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("验证码错误");
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }
    /**
     * 账户管理
     */
    public function accountManage()
    {
        $this->display();
    }

    public function order_confirm(){
        $id = I('get.id',0);
        $data = confirm_order($id);
        $user=M('users')->find($this->user_id);
        if(!$data['status'])
            $this->error($data['msg']);
		else
            if(empty(!$user['openid']))
            {$this->sendcard($user['openid'],2);}
	        $this->success($data['msg']);
    }
    /**
     *  退款售后
     */
    public function return_money(){
        $return_type=C('RETURN_TYPE');
        $count = M('return_goods')->where("user_id = {$this->user_id}")->count();
        $page = new Page($count,4);
        $list = M('return_goods')->where("user_id = {$this->user_id}")->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        foreach($list as $k =>$v){
            $map['order_sn']=$v['order_sn'];
            $map['addtime']=array('lt',$v['addtime']);
            $l=M('return_goods')->where($map)->select();
            $str=array();
            foreach($l as $k1=>$v1){
                $str[$k1]['typename']=$return_type[$v1['type']];
                $str[$k1]['date']=date("Y-m-d",$v1['addtime']);
            }
            $list[$k]['log']=$str;
            $list[$k]['typename']=$return_type[$v['type']];
            $order=M('order')->find($v['order_id']);
            $list[$k]['order_amount']=$order['order_amount'];
        }
//        show_bug($list);
        $goods_id_arr = get_arr_column($list, 'goods_id');
//        if(!empty($goods_id_arr))
//            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');

//        $this->assign('goods', $goods);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
//        if($_GET['is_ajax'])
//        {
//            $this->display('return_ajax_goods_list');
//            exit;
//        }




        $this->display();
    }
    public function return_cancel(){
        $id = I('get.id');
        $row = M('return_goods')->where(array('id'=>$id))->save(array('status'=>3));

        $this->success('取消成功');
    }

    /**
     * 申请退货
     */
    public function return_goods()
    {
        $order_id = I('order_id',0);
        $order_sn = I('order_sn',0);
        $goods_id = I('goods_id',0);

	    $spec_key = I('spec_key');
//        $order_info=M('order_goods')->where(array('order_id'=>$order_id))->find();
//        $goods_info=M('goods')->where(array('goods_id'=>$order_info['goods_id']))->find();
//        var_dump($goods_info);
//        $this->assign('goods',$goods_info);
        $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id and status in(0,1)  and spec_key = '$spec_key'")->find();
        if(!empty($return_goods))
        {
            $this->success('已经提交过退货申请!',U('Mobile/User/return_goods_info',array('id'=>$return_goods['id'])));
            exit;
        }
        if(IS_POST)
        {

    		// 晒图片
//    		if($_FILES[return_imgs][tmp_name][0])
//    		{
//    			$upload = new \Think\Upload();// 实例化上传类
//    			$upload->maxSize   =    $map['author'] = (1024*1024*3);// 设置附件上传大小 管理员10M  否则 3M
//    			$upload->exts      =    array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
//    			$upload->rootPath  =    './Public/upload/return_goods/'; // 设置附件上传根目录
//    			$upload->replace   =    true; // 存在同名文件是否是覆盖，默认为false
//    			//$upload->saveName  =  'file_'.$id; // 存在同名文件是否是覆盖，默认为false
//    			// 上传文件
//    			$upinfo  =  $upload->upload();
//    			if(!$upinfo) {// 上传错误提示错误信息
//    				$this->error($upload->getError());
//    			}else{
//    				foreach($upinfo as $key => $val)
//    				{
//    					$return_imgs[] = '/Public/upload/return_goods/'.$val['savepath'].$val['savename'];
//    				}
//    				$data['imgs'] = implode(',', $return_imgs);// 上传的图片文件
//    			}
//    		}

            $data['order_id'] = $order_id;
            $data['order_sn'] = $order_sn;
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            $data['user_id'] = $this->user_id;
            $data['type'] = I('type'); // 服务类型  退货 或者 换货
            $data['reason'] = I('reason'); // 问题描述
            $data['spec_key'] = I('spec_key'); // 商品规格
            M('return_goods')->add($data);
            $this->success('申请成功,客服第一时间会帮你处理',U('Mobile/User/order_list'));
            exit;
        }

        $goods = M('goods')->where("goods_id = $goods_id")->find();
        $order = M('order')->where("order_id = $order_id")->find();
//        show_bug(I());exit;
        $this->assign('goods',$goods);
        $this->assign('order',$order);
        $this->assign('order_id',$order_id);
        $this->assign('order_sn',$order_sn);
        $this->assign('goods_id',$goods_id);
        $this->display();
    }
    /**
     * 退换货列表
     */
    public function return_goods_list()
    {
        $return_type=C('RETURN_TYPE');
        $count = M('return_goods')->where("user_id = {$this->user_id}")->count();
        $page = new Page($count,4);
        $list = M('return_goods')->where("user_id = {$this->user_id}")->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        foreach($list as $k =>$v){
            $map['order_sn']=$v['order_sn'];
            $map['addtime']=array('lt',$v['addtime']);
            $l=M('return_goods')->where($map)->select();
            $str=array();
            foreach($l as $k1=>$v1){
                $str[$k1]['typename']=$return_type[$v1['type']];
                $str[$k1]['date']=date("Y-m-d",$v1['addtime']);
            }
            $list[$k]['log']=$str;
            $list[$k]['typename']=$return_type[$v['type']];
        }
//        show_bug($list);
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
        if($_GET['is_ajax'])
        {
            $this->display('return_ajax_goods_list');
            exit;
        }
    	$this->display();
    }

    /**
     *  退货详情
     */
    public function return_goods_info()
    {
        $id = I('id',0);
        $return_goods = M('return_goods')->where("id = $id")->find();
        if($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $goods = M('goods')->where("goods_id = {$return_goods['goods_id']} ")->find();

        $this->assign('goods',$goods);
        $this->assign('return_goods',$return_goods);
        $this->display();
    }

    /**
     * 退换货列表
     */
    public function fenqi_list()
    {

        $statusname=array('审核中','通过','未通过');

        $count = M('fenqi')->where("user_id = {$this->user_id}")->count();
        $page = new Page($count,4);
        $list = M('fenqi')->where("user_id = {$this->user_id}")->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
        if($_GET['is_ajax'])
        {
            $this->display('fenqi_ajax_list');
            exit;
        }
        $this->display();
    }


    /**
     *  个人资料
     */
    public function person(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $inviteid=sprintf("%06d",$user_info['user_id']);
        $inviteurl=$_SERVER['HTTP_HOST'].U('reg',array('invite'=>$inviteid));
        $this->assign('inviteurl',$inviteurl);
        $this->assign('User',$user_info);
        $this->display();
    }

    /**
     *  我的预约
     */
    public function apoint_list(){
        $apoint_lists =M('apointment')->where(array('user_id'=>$this->user_id))->select();
        $where=1;
        $where.=" and  a.store_id = s.id and a.type_id=t.id and a.user_id=".$this->user_id;
        $apoint_lists = M()->table('apointment a,store s,apointtype t')->field('s.name,s.address,a.*,t.type_name')->where($where)->order('a.id desc')->select();

        $this->assign('list',$apoint_lists);
        $this->display();
    }
    public function apointment(){
        $status_name=array('','预约中','预约结束');
        $id=I('id',0);
        $where=1;
        $where.=" and  a.store_id = s.id and a.type_id=t.id and a.id=".$id;
        $apoint = M()->table('apointment a,store s,apointtype t')->field('s.name,s.address,a.*,t.type_name')->where($where)->order('a.id desc')->find();
        $apointtypelist=M('apointtype')->select();
        $this->assign('apointtypelist',$apointtypelist);
        $apoint['statusname']=$status_name[$apoint['status']];
        $this->assign('apoint',$apoint);
        $this->display();
    }
    public function apointedit(){
        if(IS_POST){
            $data=array(
                'username'=>I('post.username',''),
                'mobile'=> I('post.mobile',''),
                'user_id'=> $this->user_id,
                'type_id'=> I('post.type_id',''),
                'yuyue_time'=> strtotime(I('post.yuyue_time','')),
                'addtime'=>time()
            );

            $res=M('apointment')->where(array('id'=>I('id')))->save($data);
            if($res){
                $this->success('预约成功',U('Mobile/user/apointment'));
                exit;
            }

        }
    }

    /**
     *  卡卷说明
     */
    public function coupon_detail(){
        $this->display();
    }

    //修改头像
    public function changehead(){
        $user=M('users');
//        $userinfo=$user->where(array('user_id'=>$this->user_id))->find();
        $base64 = base64_decode(str_replace('data:image/jpeg;base64,','', $_POST['ImageData']));
        $filename=md5($this->user_id).'.jpg';
        $path='./Public/upload/head_pic/'.date("Y").'/'.date("m-d").'/';
        if (!file_exists($path)) {
            $res=mkdir($path,0777);

        }

        $path1='/Public/upload/head_pic/'.date("Y").'/'.date("m-d").'/';
        $image = file_put_contents($path.$filename, $base64);

        $data['head_pic']=$path1.$filename;
        $res=$user->where(array('user_id'=>$this->user_id))->save($data);


        if($res>=0){
            $backdata["status"] = 1;
            $backdata["messages"] = "头像修改成功";
            $backdata["path"] = $path;
            $this->ajaxReturn($backdata);
        }else{
            $backdata["status"] = 2;
            $backdata["messages"] = "头像修改失败";
            $this->ajaxReturn($backdata);
        }

    }


    //积分商城相关--
    public function integral_mall(){


        $banner=M('ad')->where(array('pid'=>259))->limit(3)->select();
        $integral_goods = M('goods')->where("is_integral=1")->order('goods_id DESC')->select();//首页热卖商品

//        show_bug($integral_goods);

        $this->assign('integral_goods',$integral_goods);
        $this->assign('banner',$banner);
        $this->display();
    }
    //积分商品详情页
    public function integral_mall_detail(){
//        $address_id = I('address_id');
//        if($address_id)
//            $address = M('user_address')->where("address_id = $address_id")->find();
//        else
//            $address = M('user_address')->where("user_id = $this->user_id and is_default=1")->find();
//        if(empty($address)){
//            header("Location: ".U('Mobile/User/add_address',array('source'=>'integral')));
//        }else{
//            $this->assign('address',$address);
//        }
//        $this->display();
        C('TOKEN_ON',true);
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $goods_id = I("get.id");
        $goods = M('Goods')->where("goods_id = $goods_id")->find();
        if(empty($goods)){
            $this->tp404('此商品不存在或者已下架');
        }
        if($goods['brand_id']){
            $brnad = M('brand')->where("id =".$goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }
        $goods_images_list = M('GoodsImages')->where("goods_id = $goods_id")->select(); // 商品 图册
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id = $goods_id")->select(); // 查询商品属性表
        $filter_spec = $goodsLogic->get_spec($goods_id);

        $spec_goods_price  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        //M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数

        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $goods['sale_num'] = M('order_goods')->where("goods_id=$goods_id and is_send=1")->count();
        // 查找购物车商品总数量
        $cart_result =cookie('cart_anum');
        $this->assign('cart_total_price', $cart_result);

        $this->assign('goods_attribute',$goods_attribute);//属性值
        $this->assign('goods_attr_list',$goods_attr_list);//属性列表
//        $this->assign('catr_count',$catr_count);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $goods['discount'] = round($goods['shop_price']/$goods['market_price'],2)*10;
        $this->assign('goods',$goods);
        $this->display();

    }
    //兑换
    public function exchange(){


        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $point=$this->user['pay_points'];
        $num=I('goods_num');
        $goods_id=I('goods_id');
        $goods_spec = I("goods_spec");
        $specGoodsPriceList = M('SpecGoodsPrice')->where("goods_id = $goods_id")->getField("key,key_name,price,store_count,sku"); // 获取商品对应的规格价钱 库存 条码
        if(!empty($specGoodsPriceList) && empty($goods_spec)) // 有商品规格 但是前台没有传递过来
            exit(json_encode(array('status'=>-1,'msg'=>'必须传递商品规格','result'=>'')));

        foreach($goods_spec as $key => $val) // 处理商品规格
            $spec_item[] = $val; // 所选择的规格项
        if(!empty($spec_item)) // 有选择商品规格
        {
            sort($spec_item);
            $spec_key = implode('_', $spec_item);
            if($specGoodsPriceList[$spec_key]['store_count'] < $num)
                exit(json_encode(array('status'=>-4,'msg'=>'商品库存不足','result'=>'')));

        }

        $goods=M('goods')->where("goods_id=$goods_id")->find();
//        if(!$address_id) exit(json_encode(array('status'=>2,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态

//        if($point<$goods['integral_price']*$num){
//            $this->ajaxReturn(array('msg'=>'您的积分不够，无法兑换','status'=>-1));
//        }

//           $res=M('users')->where("user_id=$this->user_id")->setDec('pay_points',$goods['integral_price']);
        $backdata['msg']='';
        $backdata['status']=1;
        $backdata['spec_key']=$spec_key;
        $backdata['spec_key_name']=$specGoodsPriceList[$spec_key]['key_name'];
        $this->ajaxReturn($backdata);
    }
    public function integral_submit(){
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $spec_key=I('spec_key');
        $spec_key_name=I('spec_key_name');
        $address_id = I('address_id');
        if($address_id)
            $address = M('user_address')->where("address_id = $address_id")->find();
        else
            $address = M('user_address')->where("user_id = $this->user_id and is_default=1")->find();

        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'cart2')));
        }else{
            $this->assign('address',$address);
        }
        $goods_id=I('id');
         $goods=M('goods')->where(array('goods_id'=>$goods_id))->find();

//        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司


//        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('spec_key', $spec_key); // 物流公司
        $this->assign('spec_key_name', $spec_key_name); // 物流公司
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('goods', $goods); // 购物车的商品
//        show_bug($goods);
//        $this->assign('total_price', $result['total_price']); // 总计
        $this->display();
    }

    public function integral_submit1(){


            if($this->user_id == 0)
                exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态

            $address_id = I("address_id"); //  收货地址id
            $shipping_code =  I("shipping_code"); //  物流编号
//            $invoice_title = I('invoice_title'); // 发票
//            $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
//            $coupon_id =  I("coupon_id"); //  优惠券id
//            $couponCode =  I("couponCode"); //  优惠券代码
            $pay_points =  I("pay_points",0); //  使用积分
            $goods_id =  I("goods_id",0); //  使用积分
            $goods_num =  I("num",0); //  使用积分
            if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
            if(!$shipping_code) exit(json_encode(array('status'=>-4,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态

        $address = M('UserAddress')->where("address_id = $address_id")->find();
        $shipping = M('Plugin')->where("code = '$shipping_code'")->find();
            // 提交订单
            if($_REQUEST['act'] == 'submit_order')
            {
                $data = array(
                    'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
                    'user_id'          =>$this->user_id, // 用户id
                    'consignee'        =>$address['consignee'], // 收货人
                    'province'         =>$address['province'],//'省份id',
                    'city'             =>$address['city'],//'城市id',
                    'district'         =>$address['district'],//'县',
                    'twon'             =>$address['twon'],// '街道',
                    'address'          =>$address['address'],//'详细地址',
                    'mobile'           =>$address['mobile'],//'手机',
                    'zipcode'          =>$address['zipcode'],//'邮编',
                    'email'            =>$address['email'],//'邮箱',
                    'shipping_code'    =>$shipping_code,//'物流编号',
                    'shipping_name'    =>$shipping['name'], //'物流名称',
                    'invoice_title'    =>'', //'发票抬头',
                    'goods_price'      =>0.01,//'商品价格',
                    'shipping_price'   =>0,//'物流价格',
                    'user_money'       =>0,//'使用余额',
                    'coupon_price'     =>0,//'使用优惠券',
                    'integral'         =>$pay_points, //'使用积分',
                    'integral_money'   =>0,//'使用积分抵多少钱',
                    'is_integral'=>1,//是否为积分商品
                    'pay_status'=>1,
                    'total_amount'     =>0,// 订单总额
                    'order_amount'     =>0,//'应付款金额',
                    'add_time'         =>time(), // 下单时间
                    'order_prom_id'    =>0,//'订单优惠活动id',
                    'order_prom_amount'=>0,//'订单优惠活动优惠了多少钱',
                );
                $order_id = M("Order")->data($data)->add();
                if(!$order_id)
                    return array('status'=>-8,'msg'=>'添加订单失败','result'=>NULL);
                logOrder($order_id,'您提交了订单，请等待系统确认','提交订单',$this->user_id);
                $order = M('Order')->where("order_id = $order_id")->find();
                // 积分支付直接改支付状态
                  update_pay_status($order['order_sn'], 1);


                $goods = M('goods')->where("goods_id = $goods_id ")->find();
                $data2['order_id']           = $order_id; // 订单id
                $data2['goods_id']           = $goods_id; // 商品id
                $data2['goods_name']         = $goods['goods_name']; // 商品名称
                $data2['goods_sn']           = $goods['goods_sn']; // 商品货号
                $data2['goods_num']          = $goods_num; // 购买数量
                $data2['market_price']       = $goods['market_price']; // 市场价
                $data2['goods_price']        = 0.01; // 商品价
                $data2['integral_price']        = $goods['integral_price']; // 商品价
                $data2['spec_key']           = I('spec_key'); // 商品规格
                $data2['spec_key_name']      = I('spec_key_name'); // 商品规格名称
                $data2['sku']           = $goods['sku']; // 商品sku
                $data2['member_goods_price'] = 0; // 会员折扣价
                $data2['cost_price']         = $goods['cost_price']; // 成本价
                $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分
                $data2['prom_type']          = 0; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $data2['prom_id']            = 0; // 活动id
                $order_goods_id              = M("OrderGoods")->data($data2)->add();
                M('Goods')->where("goods_id = ".$goods_id)->setDec('store_count',$goods_num);//扣除库存
                M('Users')->where("user_id = $this->user_id")->setDec('pay_points',$pay_points);// 3 扣除积分

//                $result = $this->cartLogic->addOrder($this->user_id,$address_id,$shipping_code,'no',0,$car_price); // 添加订单
//                $order = M('Order')->where(array('order_id'=>$result['result']))->find();
//                $content = "美之钻订单提交成功"."<br>订单金额:".$car_price['payables']."<br>订单编号:".$order['order_sn']."<br>联系方式:".$address['mobile']."<br>联系人:".$address['consignee']."<br>下单时间:".date("Y-m-d H:i:s");//发送邮件提醒
//                send_email1('美之钻下单提醒',$content);
                exit(json_encode(array('status'=>1,'msg'=>'兑换成功','result'=>$order_id)));
            }
//            $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
//            exit(json_encode($return_arr));
        }

    //兑换记录
    public function exchange_record(){
        $where = 'is_integral=1 and user_id='.$this->user_id;
        //条件搜索
//        if(in_array(strtoupper(I('type')), array('WAITCCOMMENT','COMMENTED')))
//        {
//            $where .= " AND order_status in(1,4) "; //代评价 和 已评价
//        }
//        elseif(I('type'))
//        {
//            $where .= C(strtoupper(I('type')));
//        }
        $count = M('order')->where($where)->count();
        $Page = new Page($count,10);

        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        foreach($order_list as $k=>$v)
        {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];

        }
//        show_bug($order_list);
        $this->assign('order_status',C('ORDER_STATUS'));
        $this->assign('shipping_status',C('SHIPPING_STATUS'));
        $this->assign('pay_status',C('PAY_STATUS'));
        $this->assign('page',$show);
        $this->assign('lists',$order_list);
        $this->assign('active','order_list');
        $this->assign('active_status',I('get.type'));
        if($_GET['is_ajax'])
        {
            $this->display('ajax_exchange_list');
            exit;
        }
        $this->display();

    }
    //兑换成功页面
    public function exchange_success(){
        $this->display();
    }
    public function redbag_my(){
        $this->display();
    }
//    public function coupon_get(){
//        $usetype=I('usetype');
//        $this->assign('usetype',$usetype);
//        $page=I('post.page');
//        if(!$page){
//            $page=1;
//        }
//        $where['type']=4;
//        $where['send_end_time']=array('gt',time());
//        if($usetype>0){
//            $where['usetype']=$usetype;
//        }
//        $first_row=($page-1)*4;
//        $list=M('coupon')->where($where)->limit($first_row,4)->select();
//        foreach($list as $k=>$v){
//            $list[$k]['is_send_expire']=0;//发放是否过期
//            $list[$k]['is_use_expire']=0;//使用是否过期
//            $list[$k]['is_none']=0;//是否发完
//            $list[$k]['remaining_number']='无限制';//剩余
//            $list[$k]['is_get']=0;
//            $list[$k]['money']=intval(  $list[$k]['money']);
//            $count=M('coupon_list')->where(array('cid'=>$v['id'],'uid'=>$this->user_id))->count();
//            if($count>0){
//                $list[$k]['is_get']=1;
//            }
//            if($v['send_end_time']<time())
//                $list[$k]['is_send_expire']=1;
//            if($v['use_end_time']<time())
//                $list[$k]['is_use_expire']=1;
//            if($v['createnum']>0 ) {
//                if ($v['createnum'] <= $v['send_num']) {
//                    $list[$k]['is_none'] = 1;
//                } else {
//                    $num=$v['createnum']-$v['send_num'];
//                    $list[$k]['remaining_number']="剩余{$num}张";
//                    $list[$k]['remaining_num']=$num;
//                }
//            }
//        }
//        $this->assign('coupon_list',$list);
//        if(IS_AJAX){
//            $this->display("ajax_coupon_list_my");
//            exit;
//        }
//        $this->display();
//    }
    public function coupon_get(){
    $card_type=I('card_type');
    $this->assign('card_type',$card_type);
        $page=I('post.page');
        if(!$page){
            $page=1;
        }
//    $where['type']=4;
//    $where['send_end_time']=array('gt',time());
    if($card_type>0){
        $where['card_type']=$card_type;
        $where['isdel']=0;
        }
        $first_row=($page-1)*4;
    $list=M('cashier_wxcoupon')->where($where)->limit($first_row,4)->select();
        foreach($list as $k=>$v){
        $info=M('cashier_wxcoupon')->where(array('id'=>$v['id']))->find();
        $list[$k]['kqcontent']=unserialize($v['kqcontent']);
        $list[$k]['kqexpand']=unserialize($v['kqexpand']);
//        show_bug( $list[$k]['kqcontent']);
            $list[$k]['is_send_expire']=0;//发放是否过期
            $list[$k]['is_use_expire']=0;//使用是否过期
            $list[$k]['is_none']=0;//是否发完
            $list[$k]['remaining_number']='无限制';//剩余
            $list[$k]['is_get']=0;
            $list[$k]['money']=intval(  $list[$k]['money']);
        $count=M('cashier_wxcoupon_receive')->where(array('cid'=>$v['id'],'uid'=>$this->user_id))->count();
        if($count>=$info['get_limit']){
                $list[$k]['is_get']=1;
            }

        if($v['end_timestamp']<time())
                $list[$k]['is_use_expire']=1;
        if($v['quantity']>0 ) {
            $num=$v['quantity']-$v['receivenum'];
            if ($num<=0) {
                    $list[$k]['is_none'] = 1;
                } else {

                    $list[$k]['remaining_number']="剩余{$num}张";
                $list[$k]['remaining_num']=$num;
                }
            }
        }
        $this->assign('coupon_list',$list);
        if(IS_AJAX){
            $this->display("ajax_coupon_list_my");
            exit;
        }
        $this->display();
    }
    public function getAddInfo(){
        $cid=I('id');
        $coupon=M('cashier_wxcoupon')->where(array('id'=>$cid))->find();
        $cardTicket=$this->getcardApiTicket();
        $num=$_POST['num'];
        for($i=0;$i<$num;$i++){
            $code ='';
            $timestamp =time();
//            $card_id ='peXkJuDewFIYqpqVfoWg4WoHInPk';
            $card_id =$coupon['card_id'];
            $api_ticket =$cardTicket;
            $nonce_str =create_noncestr();
            $temp= array($code, $timestamp, $card_id,$api_ticket,$nonce_str);
            // use SORT_STRING rule
            sort($temp, SORT_STRING);
            $tmpStr = implode($temp);
            $tmpStr = sha1($tmpStr);
            $signature[$i]['timestamp']=$timestamp;
            $signature[$i]['nonce_str']=$nonce_str;
            $signature[$i]['signature']=$tmpStr;

        }
        $backdata['status']=1;
        $backdata['data']=$signature;
        $backdata['card_id']=$coupon['card_id'];;
        $this->ajaxreturn($backdata);
    }
    public function get_coupon(){
        $cid=I('id');
        $user=M('users')->find($this->user_id);
        M('cashier_wxcoupon_receive')->where(array('openid'=>$user['openid']))->save(array('user_id'=>$this->user_id));
//        $coupon=M('cashier_wxcoupon')->where(array('id'=>$cid))->find();
//        $count=M('cashier_wxcoupon_receive')->where(array('cid'=>$cid,'uid'=>$this->user_id))->count();
//        if($count>0){
//            $this->ajaxReturn(array('status'=>-1,'msg'=>'你已经领取过了'));
//         }
//        $data1['receivenum']=$coupon['receivenum']+1;
//
//        M('cashier_wxcoupon')->where(array('id'=>$cid))->save($data1);
//        $data['cid']=$cid;
//        $data['type']=$coupon['type'];
//        $data['usetype']=$coupon['usetype'];
//        $data['uid']=$this->user_id;
//        $data['send_time']=time();
//        M('cashier_wxcoupon_receive')->add($data);

        $this->ajaxReturn(array('status'=>1,'msg'=>'领取成功'));
    }

    public function withdraw_set(){
        $banklist = M('bank')->where(array('user_id'=> $this->user_id))->select();

        $op=I('op');

        if($op=='del'){
            $id=I('id');
            $result = M('bank')->where(array('id'=> $id))->delete();
            if($result){
                $this->success('删除成功');
                exit;
            }
        }

        $this->assign('banklist',$banklist);
        $this->display();
    }

    /*
     * 添加银行卡
     */
    public function add_bankcard()
    {
        if(IS_POST)
        {
            $logic = new UsersLogic();
            $data = $logic->add_bank($this->user_id,0,I('post.'));
            if($data['status'] != 1)
                $this->error($data['msg']);
//            elseif($_POST['source'] == 'cart2')
//            {
//                header ('Location:'.U('/Mobile/Cart/cart2',array('address_id'=>$data['result'])));
//                exit;
//            }

            $this->success($data['msg'],U('/Mobile/User/withdraw_set'));
            exit();
        }
        $id = I('id');
        $bank = M('bank')->where(array('id'=>$id,'user_id'=> $this->user_id))->find();
        $this->assign('bank',$bank);
        $this->display();

    }

    public function sign(){

        $set = getSet();

//        if (!empty($set['sign_rule'])) {
//            $set['sign_rule'] = iunserializer($set['sign_rule']);
//            $set['sign_rule'] = htmlspecialchars_decode($set['sign_rule']);
//        }

        $month = getMonth();
        $calendar = getCalendar($this->user_id);
        $signinfo = getsign1($this->user_id);
        $advaward = getAdvAward($this->user_id);

//        $json_arr = array('calendar' => $calendar, 'signinfo' => $signinfo, 'advaward' => $advaward, 'year' => date('Y', time()), 'month' => date('m', time()), 'today' => date('d', time()), 'signed' => $signinfo['signed'], 'signold' => $set['signold'], 'signoldprice' => $set['signold_price'], 'signoldtype' => (empty($set['signold_type']) ? $set['textmoney'] : $set['textcredit']), 'textsign' => $set['textsign'], 'textsigned' => $set['textsigned'], 'textsignold' => $set['textsignold'], 'textsignforget' => $set['textsignforget']);
//        $json = json_encode($json_arr);
//        setShare($set);
        $texts = array('sign' => $set['textsign'], 'signed' => $set['textsigned'], 'signold' => $set['textsignold'], 'credit' => $set['textcredit'], 'color' => $set['maincolor']);
        $this->assign('texts',$texts);
        $this->assign('month',$month);
        $this->assign('calendar',$calendar);
        $this->assign('signinfo',$signinfo);
        $this->assign('advaward',$advaward);
        $this->display();
    }

    public function dosign(){

        if (!I('is_ajax')) {
            $this->ajaxReturn(array('status'=>-1,"msg"=>'错误的请求'));
        }

        $set =getSet();

        $date = trim(I('date'));
        $signinfo =getsign1($this->user_id,$date);

        if (!empty($signinfo['signed'])) {
            $this->ajaxReturn(array('status'=>-1,"msg"=>'已经签到，不要重复' . $set['textsign'] . '哦~') );
        }


        if (!empty($date) && (time() < strtotime($date))) {
            $this->ajaxReturn(array('status'=>-1,"msg"=>$set['textsign'] . '日期大于当前日期!') );
        }


        $users = M('users')->find($this->user_id);
        $credit = 0;

        if (!empty($set['reward_default_day']) && (0 < $set['reward_default_day'])) {
            $credit = $set['reward_default_day'];
            $message = '日常签到';
            $message .= $set['reward_default_day'] ;
        }


        if (!empty($set['reward_default_first']) && (0 < $set['reward_default_first']) && empty($signinfo['sum']) && empty($date)) {
            $credit = $set['reward_default_first'];
            $message = '首次签到积分+' . $set['reward_default_first'];
        }


//        if (!empty($reword_special) && empty($date)) {
//            foreach ($reword_special as $item ) {
//                $day = date('Y-m-d', $item['date']);
//                $today = date('Y-m-d', time());
//                if (($day === $today) && !empty($item['credit'])) {
//                    $credit = $credit + $item['credit'];
//                    if (!empty($message)) {
//                        $message .= "\r\n";
//                    }
//                    $message .= ((empty($item['title']) ? $today : $item['title']));
//                    $message .= $set['textsign'] . '+' . $item['credit'] . $set['textcredit'];
//                    break;
//                }
//            }
//        }

        if (!empty($credit) && (0 < $credit)) {
            $data['pay_points']=$users['pay_points']+$credit;
            M('users')->where(array('user_id'=>$this->user_id))->save($data);
        }

        $arr = array(
            'time' => (empty($date) ? time() : strtotime($date)),
            'user_id' => $this->user_id,
            'credit' => $credit, 'log' => $message);
        M('sign_records')->add($arr);
//        pdo_insert('sign_records',$arr);
        $signinfo = getsign1($this->user_id);
        $result = array('message' =>  '签到成功!' . $message, 'signorder' => $signinfo['orderday'], 'signsum' => $signinfo['sum'], 'addcredit' => $credit, 'credit' => intval($data['pay_points']));
        updateSign($this->user_id,$signinfo);
        $this->ajaxReturn(array('status'=>1,"msg"=>$result));
    }
    public function getAdvAward()
    {
        $set = getSet();
        $advaward =getAdvAward($this->user_id);
        $this->assign('set',$set);
        $this->assign('advaward',$advaward);
         $this->display('sign_advaward');
    }
    public function doreward()
    {
        if (!I('is_ajax')) {
            $this->ajaxReturn(array('status'=>-1,"msg"=>'错误的请求'));
        }
        $type = intval(I('type'));
        $day = intval(I('day'));
        if (empty($type) || empty($day)) {
            $this->ajaxReturn(array('status'=>-1,"msg"=>'请求参数错误!') );
        }
        $set =getSet();
        $reword_sum = unserialize($set['reword_sum']);
        $reword_order = unserialize($set['reword_order']);
        $condition = '';
        if (!empty($set['cycle'])) {
            $month_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $month_end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
            $condition .= ' and `time` between ' . $month_start . ' and ' . $month_end . ' ';
        }
//        $record = pdo_fetch('select * from ' . tablename('ewei_shop_sign_records') . ' where openid=:openid and `type`=' . $type . ' and `day`=' . $day . ' and uniacid=:uniacid ' . $condition . ' limit 1 ', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
        $where=" user_id=$this->user_id and type=$type and day = $day ".$condition;
        $record=M('sign_records')->where($where)->find();
        if (!empty($record)) {
            $this->ajaxReturn(array('status'=>-1,"msg"=>'此奖励已经领取, 请不要重复领取!') );
        }
        $users = M('users')->find($this->user_id);
        $credit = 0;
        if (($type == 1) && !empty($reword_order)) {
            foreach ($reword_order as $item ) {
                if (($item['day'] == $day) && !empty($item['credit'])) {
                    $credit = $item['credit'];
                }
            }
            $message = '连续签到';
        }
        else if (($type == 2) && !empty($reword_sum)) {
            foreach ($reword_sum as $item ) {
                if (($item['day'] == $day) && !empty($item['credit'])) {
                    $credit = $item['credit'];
                }
            }
            $message = '总签到' ;
        }
        $message .= $day . '天获得奖励' . $credit . '积分';

        if (!empty($credit) && (0 < $credit)) {
            $data['pay_points']=$users['pay_points']+$credit;
            M('users')->where(array('user_id'=>$this->user_id))->save($data);
//            m('member')->setCredit($_W['openid'], 'credit1', +$credit,  '积分签到: ' . $message);
        }


        $arr = array( 'time' => time(), 'user_id' => $this->user_id, 'credit' => $credit, 'log' => $message, 'type' => $type, 'day' => $day);

        M('sign_records')->add($arr);

        $result = array('message' => '领取成功!' . $message, 'addcredit' => $credit, 'credit' => intval($data['pay_points']));
        $this->ajaxReturn(array('status'=>1,"msg"=>$result));
    }
    //充值
    public  function recharge(){
        $order_id = I('order_id');
        $paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and  scene in(0,1)")->select();
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code='weixin'")->select();
        }
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
        $bank_img = include 'Application/Home/Conf/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('bankCodeList',$bankCodeList);

        if($order_id>0){
            $order = M('recharge')->where("order_id = $order_id")->find();
            $this->assign('order',$order);
        }
        $this->display();
    }

    /**
     * 申请提现记录
     */
    public function withdrawals(){

        C('TOKEN_ON',true);
        if(IS_POST)
        {
            $this->verifyHandle('withdrawals');
            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $data['create_time'] = time();
            $distribut_min = tpCache('distribut.min'); // 最少提现额度
            if($data['money'] < $distribut_min)
            {
                $this->error('每次最少提现额度'.$distribut_min);
                exit;
            }
            if($data['money'] > $this->user['user_money'])
            {
                $this->error("你最多可提现{$this->user['user_money']}账户余额.");
                exit;
            }

            if(M('withdrawals')->add($data)){
                $this->success("已提交申请");
                exit;
            }else{
                $this->error('提交失败,联系客服!');
                exit;
            }
        }

        $where = " user_id = {$this->user_id}";
        $count = M('withdrawals')->where($where)->count();
        $page = new Page($count,16);
        $list = M('withdrawals')->where($where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('list',$list); // 下线
        if($_GET['is_ajax'])
        {
            $this->display('ajaxx_withdrawals_list');
            exit;
        }
        $this->display();
    }

    public function shop(){
       $store= M('store')->where(array('status'=>1))->select();
        $this->assign('storelist',$store);
        $this->display();
    }
    public function shopmap(){
        $id=I('id');
        $store= M('store')->find($id);
        $zuobiao=explode(",",$store['zuobiao']);
        $store['lat']=$zuobiao[0];
        $store['lnt']=$zuobiao[1];
        $this->assign('store',$store);
        $this->display();
    }
    public function prize(){

        $map['is_open']=1;

        $map['starttime']=array('lt',time());
        $map['endtime']=array('gt',time());

        $prizelist=M("prize_draw")->where($map)->find();

        if(!$prizelist)
            $this->error('抽奖活动还未开始');

        if($this->user['name']==0 || $this->user['mobile']=='')
            $this->error('请先把信息填写完整，方便联系',U("person"));
        $prize=M('prize')->where(array('pdid'=>$prizelist['id'],'enabled'=>1))->order('orderby asc')->select();
        $names= get_arr_column($prize,'name');
        $icons= get_arr_column($prize,'icon');
        $num=count($names);
        //两种颜色循环
        for($i=1;$i<=$num;$i++){
            if($i%2 ==1){
                $color[]="#ff7f7e";
            }else{
                $color[]="#ff5959";
            }
        }



        $names=json_encode($names);
        $icons=json_encode($icons);
        $color=json_encode($color);
        $this->assign('names',$names);
        $this->assign('icons',$icons);
        $this->assign('color',$color);
        $this->assign('prize',$prize);
        $this->assign('prizelist',$prizelist);
        $this->display();
    }
    //检测是否有抽奖权限
    public function prize_check(){

        $pdid=I('pdid');
        if(!$this->user_id)
            $this->ajaxReturn(array('status'=>0,'msg'=>'请先登录'));
        $prize_draw = M('prize_draw')->where(array('id'=>$pdid))->find();
        $datenum=date("ymd");
        $nums=M('prize_list')->where(array('uid'=>$this->user_id,'getday'=>$datenum))->count();
        if($nums>=$prize_draw['limit_num'])
            $this->ajaxReturn(array('status'=>0,'msg'=>'次数已用完'));

        $item=0;
        $prize_arr = M('prize')->where(array('pdid'=>$pdid,'enabled'=>1))->order('orderby asc')->select();
        foreach ($prize_arr as $k => $v) {
            $arr[$v['prize_id']] = $v['chance'];
        }
        $prize_id = $this->get_prize($arr);
        foreach($prize_arr as  $k => $v){
            if($prize_id==$v['prize_id'])
                $item=$k+1;
        }

        $prize= M('prize')->where(array("prize_id"=>$prize_id))->find();
        if($prize['type']==1){
            $jdata['uid'] = $this->user_id;
            $jdata['pdid'] = $pdid;
            $jdata['prize_id'] = $prize['prize_id'];
            $jdata['send_time'] = time();
            $jdata['getday'] = date("ymd");
            M('prize_list')->add($jdata);
            $this->ajaxReturn(array('item'=>$item,'status'=>1,'title'=>'中奖了','msg'=>"恭喜您抽中".$prize['name']));
        }elseif($prize['type']==2 ){
            $this->ajaxReturn(array('item'=>$item,'status'=>1,'title'=>'谢谢惠顾','msg'=>"很遗憾您未中奖"));
        }

    }
    public function prize_list(){
        $typename=array('未领取','已领取');
        $where=" uid =$this->user_id";
        $sql="select pl.*,p.name,p.icon from prize_list pl LEFT JOIN prize p on pl.prize_id =p.prize_id WHERE $where ORDER BY id desc";
        $prize_list=M()->query($sql);
        foreach($prize_list as $k=>$v){
            $prize_list[$k]['typename']=$typename[$v['type']];
        }
        $this->assign('prize_list',$prize_list);
        $this->display();
    }
    //遇到数量为0的继续抽，知道不为0
    public function get_prize($arr){
        $prize_id = $this->get_rand($arr);
        $prize= M('prize')->where(array("id"=>$prize_id))->find();
        if($prize['now_num']==0){
           $this-> get_prize($arr);
        }else{
          return $prize_id;
        }
    }



    public function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    public function myson(){

        $list= M('users')->where("invite_id=".$this->user_id)->select();
        $this->assign('list',$list);

        $this->display();

    }
    public function invite(){

        $list= M('account_log')->where("type = 1 and is_now=1 and user_id=".$this->user_id)->select();
        foreach($list as $k=>$v){
            $list[$k]['next_nickname']=M('users')->where(array('user_id'=>$v['sid']))->getField('nickname');
        }
        $this->assign('list',$list);
        $this->display();
    }
    public function commission(){
        $list= M('account_log')->where("type = 1 and is_now=1 and user_id=".$this->user_id)->select();
        foreach($list as $k=>$v){
            $list[$k]['next_nickname']=M('users')->where(array('user_id'=>$v['sid']))->getField('nickname');
        }

        $this->assign('list',$list);
        $this->display();

    }
}