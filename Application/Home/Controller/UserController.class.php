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
namespace Home\Controller;

use Home\Logic\UsersLogic;
use Think\Page;
use Think\Verify;

class UserController extends BaseController
{

    public $user_id = 0;
    public $user = array();

    public function _initialize()
    {
        parent::_initialize();
        if (session('?User')) {
            $user = session('User');
            $user = M('users')->where("user_id = {$user['user_id']}")->find();
            session('User', $user);  //覆盖session 中的 User
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('User', $user); //存储用户信息
            $this->assign('user_id', $this->user_id);
        } else {
            $nologin = array(
                'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
                'verifyHandle', 'reg', 'send_sms_reg_code', 'identity', 'check_validate_code',
                'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'info', 'infoEdit'
            );
            if (!in_array(ACTION_NAME, $nologin)) {
                header("location:" . U('Home/User/login'));
                exit;
            }
        }
        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user', $navigate_user);
    }

    /*
     * 用户中心首页
     */
    public function index()
    {
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $user = $user['result'];
        $level = M('user_level')->select();
        $level = convert_arr_key($level, 'level_id');
        $this->assign('level', $level);
        $this->assign('user', $user);
        $this->display();
    }


    public function logout()
    {
        cookie('uname', null);
        cookie('cart_anum', null);
        cookie('user_id', null);
        session('User', null);
        /*setcookie('uname','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');*/
        // session_unset();
        // session_destroy();
        //$this->success("退出成功",U('Home/Index/index'));
        header("location:" . U('/Home'));
        exit;
    }

    public function research()
    {

        $research_list = M('research_list')->where('is_star =1')->limit(1)->find();
        $id = I('id');
        if ($id) {
            $where['pid'] = $id;
        } else {
            $where['pid'] = $research_list['id'];
        }
        $where['enabled'] = 1;

        $this->assign('research_list', $research_list);
        $question = M('research')->where($where)->order('orderby desc')->select();
        foreach ($question as $k => $v) {
            $question[$k]['option'] = unserialize($v['option']);
        }
        if (IS_AJAX) {
            if (!$this->user_id) {
                $this->ajaxReturn(array('status' => -1, 'msg' => "请先登录"));
            }
            $research_count = M('research_log')->where(array('user_id' => $this->user_id, 'pid' => $where['pid']))->count();
            if ($research_count > 0) {
                $this->ajaxReturn(array('status' => -1, 'msg' => "您已经参与过调查，请务重复提交"));
            }
            $ids = I('ids');
            $sort = I('sort');
            $data2['user_id'] = $this->user_id;
            $data2['pid'] = $where['pid'];
            foreach ($ids as $k => $v) {
                $qs = M('research')->find($v);
                $option = unserialize($qs['option']);
                foreach ($option as $k1 => $v1) {
                    if ($sort[$k] == $v1['sort']) {
                        $num = $v1['count'] + 1;
                        $option[$k1]['count'] = $num;
                    }
                }
                $data['sum'] = array("exp", 'sum+1');
                $data['option'] = serialize($option);
                M('research')->where(array("id" => $v))->save($data);

            }
            M('research_log')->add($data2);
            $this->ajaxReturn(array('status' => 1, 'msg' => "投票成功"));
        }
        $this->assign('question', $question);
        $this->display("Index/research");
    }


    /*
     * 账户资金
     */
    public function account()
    {
        $user = session('User');
        $type = I('get.type', 0);
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id, $type);
        $account_log = $data['result'];

        $this->assign('User', $user);
        $this->assign('account_log', $account_log);
        $this->assign('page', $data['show']);
        $this->assign('type', $type);
        $this->display();
    }

    /*
     * 优惠券列表
     */
    public function coupon()
    {
        $logic = new UsersLogic();
        $type = I('get.type', 0);
        $data = $logic->get_coupon($this->user_id, $type);
        $coupon_list = $data['result'];
        foreach ($coupon_list as &$item) {
            if ($item['order_id'] > 0 && $item['use_time'] > 0) {
                $item['status'] = 1;    //已使用
            } else if ($item['use_time'] == 0 && $item['use_end_time'] > time()) {
                $item['status'] = 0;    //未使用
            } else if (time() > $item['use_end_time']) {
                $item['status'] = 2;    //已过期
            }
        }
        $this->assign('coupon_list', $coupon_list);
        $this->assign('page', $data['show']);
        $this->assign('type', $type);
        $this->assign('active', 'coupon');
        $this->display();
    }

    /**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl', $referurl);
        $this->display();
    }

    public function pop_login()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Home/User/index");
        $this->assign('referurl', $referurl);
        $this->display();
    }

    public function do_login()
    {
        $username = I('post.username');
        $password = I('post.password');
        $username = trim($username);
        $password = trim($password);
        $verify_code = I('post.verify_code');

        $verify = new Verify();
        if (!tpCache('verify_code.reg_show')) {
            if (!$verify->check($verify_code, 'user_login')) {
                $res = array('status' => 0, 'msg' => '验证码错误');
                exit(json_encode($res));
            }
        }

        $logic = new UsersLogic();
        $res = $logic->login($username, $password);

        if ($res['status'] == 1) {
            $res['url'] = urldecode(I('post.referurl'));
            session('User', $res['result']);
            setcookie('user_id', $res['result']['user_id'], null, '/');
            setcookie('is_distribut', $res['result']['is_distribut'], null, '/');
            $nickname = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('uname', urlencode($nickname), null, '/');
            setcookie('cart_anum', '', time() - 3600, '/');
            $cartLogic = new \Home\Logic\CartLogic();
            $cartLogic->login_cart_handle($this->session_id, $res['result']['user_id']);  //用户登录后 需要对购物车 一些操作
        }
        exit(json_encode($res));
    }

    /**
     *  注册
     */
    public function reg()
    {
        $invite = $_GET['invite'];
        $this->assign('invite', $invite); // 手机短信超时时间
        if ($this->user_id > 0) header("Location: " . U('Home/User/Index'));
        if (IS_AJAX) {
            $logic = new UsersLogic();
            //验证码检验
            if (!tpCache('sms.regis_sms_enable')) $this->verifyHandle('user_reg');
            $username = I('post.username', '');
            $invite_id = intval(I('post.invite_id', ''));
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            $validated = 0; //是否已验证邮箱或手机
            //是否开启注册验证码机制
            if (check_mobile($username) && tpCache('sms.regis_sms_enable')) {
                $code = I('post.code', '');
                if (!$code)
                    $this->error('请输入短信验证码');
                $check_code = $logic->sms_code_verify($username, $code, $this->session_id);
                if ($check_code['status'] != 1) {
                    $this->error($check_code['msg']);
                } else {
                    $validated = 1; //已验证手机
                }
            }
            $data = $logic->reg($username, $password, $password2, $validated, $invite_id);
            if ($data['status'] != 1)
                $this->error($data['msg']);
            session('User', $data['result']);
            setcookie('user_id', $data['result']['user_id'], null, '/');
            setcookie('is_distribut', $data['result']['is_distribut'], null, '/');
            $nickname = empty($data['result']['nickname']) ? $username : $data['result']['nickname'];
            setcookie('uname', $nickname, null, '/');
            $cartLogic = new \Home\Logic\CartLogic();
            $cartLogic->login_cart_handle($this->session_id, $data['result']['user_id']);  //用户登录后 需要对购物车 一些操作

            $this->success($data['msg'], U('Home/User/reg_success'));
            exit;
        }
        $this->assign('regis_sms_enable', tpCache('sms.regis_sms_enable')); // 注册启用短信：
        $sms_time_out = tpCache('sms.sms_time_out') > 0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        $this->display();
    }

    /**
     * [reg_success 注册完成]
     * @return [type] [description]
     */
    public function reg_success()
    {
        $user = session('User');
        $this->assign('user', $user);
        $this->display("reg_success");
    }
    /*
         * 订单列表
         */
    public function order_design_list()
    {
        $status=array(1=>'待确认',2=>'已确认',3=>'预约完成',4=>'取消',);

        $where = ' user_id=' . $this->user_id;
        //条件搜索

        $count = M('apointment_design')->where($where)->count();
        $Page = new Page($count, 5);
        $Page->setConfig('prev', '&nbsp;');
        $Page->setConfig('next', '&nbsp;');

        $show = $Page->show();
        $order_str = "id DESC";
        $order_list = M('apointment_design')->order($order_str)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($order_list as $k=>$v){
            $order_list[$k]['order_status_desc']=$status[$v['status']];
        }
//        show_bug($order_list);
        $design =M('design')->find($order_list['design_id']);
        //获取订单商品


        $this->assign('order_status', C('ORDER_STATUS'));
        $this->assign('shipping_status', C('SHIPPING_STATUS'));
        $this->assign('pay_status', C('PAY_STATUS'));
        $this->assign('page', $show);
        $this->assign('lists', $order_list);
        $this->assign('design', $design);
        $this->assign('active', 'order_design_list');
        $this->assign('active_status', I('get.type'));
        $this->display();
    }
    /*
     * 订单列表
     */
    public function order_list()
    {
        $where = ' user_id=' . $this->user_id;
        //条件搜索
        if (I('get.type')) {
            $where .= C(strtoupper(I('get.type')));
        }
        // 搜索订单 根据商品名称 或者 订单编号
        $search_key = trim(I('search_key'));
        if ($search_key) {
            $goods = M()->query("select order_id from `__PREFIX__order_goods` where goods_name like '%$search_key%'");
            $order_id = '';
            foreach ($goods as $item) {
                $order_id .= $item['order_id'] . ',';
            }
            if ($order_id) {
                $order_id = "or order_id in (" . rtrim($order_id, ',') . ")";
            }
            $where .= " and (order_sn like '%$search_key%') $order_id";
        }
        $count = M('order')->where($where)->count();
        $Page = new Page($count, 5);
        $Page->setConfig('prev', '&nbsp;');
        $Page->setConfig('next', '&nbsp;');
        $Page->parameter['search_key'] = $search_key;
        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
        $this->assign('order_status', C('ORDER_STATUS'));
        $this->assign('shipping_status', C('SHIPPING_STATUS'));
        $this->assign('pay_status', C('PAY_STATUS'));
        $this->assign('page', $show);
        $this->assign('lists', $order_list);
        $this->assign('active', 'order_list');
        $this->assign('active_status', I('get.type'));
        $this->display();
    }

    public function test()
    {
        $json = '{
            "message":"ok","status":"1","state":"3","data":[
  {"time":"2012-07-07 13:35:14","context":"客户已签收"},
  {"time":"2012-07-07 09:10:10","context":"离开[北京石景山营业厅]派送中，递送员[温]，电话[]"},
  {"time":"2012-07-06 19:46:38","context":"到达[北京石景山营业厅]"},
  {"time":"2012-07-06 15:22:32","context":"离开[北京石景山营业厅]派送中，递送员[温]，电话[]"},
  {"time":"2012-07-06 15:05:00","context":"到达[北京石景山营业厅]"},
  {"time":"2012-07-06 13:37:52","context":"离开[北京_同城中转站]发往[北京石景山营业厅]"},
  {"time":"2012-07-06 12:54:41","context":"到达[北京_同城中转站]"},
  {"time":"2012-07-06 11:11:03","context":"离开[北京运转中心驻站班组] 发往[北京_同城中转站]"},
  {"time":"2012-07-06 10:43:21","context":"到达[北京运转中心驻站班组]"},
  {"time":"2012-07-05 21:18:53","context":"离开[福建_厦门支公司] 发往 [北京运转中心_航空]"},
  {"time":"2012-07-05 20:07:27","context":"已取件，到达 [福建_厦门支公司]"}
]}';
        $result = json_decode($json, true);
        $data = $result['data'];
        sort($data);
        $a = array();
        $c = array();
        $xingqi = array('周六', '周日', '周一', '周二', '周三', '周四', '周五',);

        $mydat = array();
        foreach ($data as $k => $v) {
            $b = date('Y-m-d', strtotime($v['time']));
            $zhou = $xingqi[date('N', strtotime($v['time']))];
            $b .= " $zhou";
            $a[$b][] = $v;
            $res = strpos($v['context'], '已取件');
            if ($res !== false) {
                $mydat["待揽件"] = $v['time'];
            }
            $res1 = strpos($v['context'], '运转');
            if ($res1 !== false) {
                $mydat["运转"] = $v['time'];
            }
            $res2 = strpos($v['context'], '派送中');
            if ($res2 !== false) {
                $mydat["派送"] = $v['time'];
            }
            $res3 = strpos($v['context'], '已签收');
            if ($res3 !== false) {
                $mydat["已签收"] = $v['time'];
            }
        }
        show_bug($mydat);
    }

    /*
    * 物流详情
    */
    public function express()
    {
        $xingqi = array('周六', '周日', '周一', '周二', '周三', '周四', '周五',);
        $order_id = I('get.order_id', 195);
        $result = $order_goods = $delivery = array();
        $map['order_id'] = $order_id;
        $map['user_id'] = $this->user_id;
        $region_list = get_region_list();
        $order_info = M('order')->where($map)->find();
        $order_goods = M('order_goods')->where("order_id=$order_id")->select();
        $delivery = M('delivery_doc')->where("order_id=$order_id")->limit(1)->find();
        if ($delivery['shipping_code'] && $delivery['invoice_no']) {
            // if(1){
            $result = queryExpress($delivery['shipping_code'], $delivery['invoice_no']);
            // $result = queryExpress("yunda","1000782579452");
            // print_r($result);die;
//            $json='{
//            "message":"ok","status":"1","state":"3","data":[
//  {"time":"2012-07-07 13:35:14","context":"客户已签收"},
//  {"time":"2012-07-07 09:10:10","context":"离开[北京石景山营业厅]派送中，递送员[温]，电话[]"},
//  {"time":"2012-07-06 19:46:38","context":"到达[北京石景山营业厅]"},
//  {"time":"2012-07-06 15:22:32","context":"离开[北京石景山营业厅]派送中，递送员[温]，电话[]"},
//  {"time":"2012-07-06 15:05:00","context":"到达[北京石景山营业厅]"},
//  {"time":"2012-07-06 13:37:52","context":"离开[北京_同城中转站]发往[北京石景山营业厅]"},
//  {"time":"2012-07-06 12:54:41","context":"到达[北京_同城中转站]"},
//  {"time":"2012-07-06 11:11:03","context":"离开[北京运转中心驻站班组] 发往[北京_同城中转站]"},
//  {"time":"2012-07-06 10:43:21","context":"到达[北京运转中心驻站班组]"},
//  {"time":"2012-07-05 21:18:53","context":"离开[福建_厦门支公司] 发往 [北京运转中心_航空]"},
//  {"time":"2012-07-05 20:07:27","context":"已取件，到达 [福建_厦门支公司]"}
//]}';
//            $result=json_decode($json,true);
            $data = $result['data'];
            sort($data);
            $detail = array();//运单详情
            $c = array();
            foreach ($data as $k => $v) {
                $date = date('Y-m-d', strtotime($v['time']));
                $zhou = $xingqi[date('N', strtotime($v['time']))];
                $date .= " $zhou";
                $detail[$date][] = $v;
                $res = strpos($v['context'], '已取件');
                if ($res !== false) {
                    $mydat["待揽件"] = $v['time'];
                }
                $res1 = strpos($v['context'], '运转');
                if ($res1 !== false) {
                    $mydat["运转"] = $v['time'];
                }
                $res2 = strpos($v['context'], '派送中');
                if ($res2 !== false) {
                    $mydat["派送"] = $v['time'];
                }
                $res3 = strpos($v['context'], '已签收');
                if ($res3 !== false) {
                    $mydat["签收"] = $v['time'];
                }
            }
//            foreach($detail as $k =>$v){
//                show_bug($v);
//            }
//            show_bug($zhou);
            $this->assign('mydat', $mydat);
            $this->assign('detail', $detail);
            $this->assign('order_info', $order_info);
            $this->assign('result', $result);
            $this->assign('order_goods', $order_goods);
            $this->assign('delivery', $delivery);
            $this->assign('region_list', $region_list);
        }
        $this->assign('active', 'order_list');
        $this->display();
    }

    /*
     * 订单详情
     */
    public function order_detail()
    {
        $id = I('get.id');

        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性

        if (!$order_info) {
            $this->error('没有获取到订单信息');
            exit;
        }
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        //$order_info['total_fee'] = $order_info['goods_price'] + $order_info['shipping_price'] - $order_info['integral_money'] -$order_info['coupon_price'] - $order_info['discount'];
        //获取订单进度条
        $sql = "SELECT action_id,log_time,status_desc,order_status FROM ((SELECT * FROM `__PREFIX__order_action` WHERE order_id = $id AND status_desc <>'' ORDER BY action_id) AS a) GROUP BY status_desc ORDER BY action_id";
        $items = M()->query($sql);
        $items_count = count($items);
        $region_list = get_region_list();

        $invoice_no = M('DeliveryDoc')->where("order_id = $id")->getField('invoice_no', true);
        $order_info[invoice_no] = implode(' , ', $invoice_no);
        //获取订单操作记录
//        show_bug($order_info);
        $order_action = M('order_action')->where(array('order_id' => $id))->select();
        $this->assign('order_status', C('ORDER_STATUS'));
        $this->assign('shipping_status', C('SHIPPING_STATUS'));
        $this->assign('pay_status', C('PAY_STATUS'));
        $this->assign('region_list', $region_list);
        $this->assign('order_info', $order_info);
        $this->assign('order_action', $order_action);
        $this->assign('active', 'order_list');
        $this->display();
    }

    /*
     * 取消订单
     */
    public function cancel_order()
    {
        $id = I('get.id');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id, $id);
        if ($data['status'] < 0)
            $this->error($data['msg']);
        $this->success($data['msg']);
    }
//    /*
//        * 取消订单
//        */
//    public function cancel_order()
//    {
//        $id = I('get.id');
//        //检查是否有积分，余额支付
//        $logic = new UsersLogic();
//        $data = $logic->cancel_order($this->user_id, $id);
//        if ($data['status'] < 0)
//            $this->error($data['msg']);
//        $this->success($data['msg']);
//    }
    /*
     * 用户地址列表
     */
    public function address_list()
    {
        $address_lists = get_user_address_list($this->user_id);
        $region_list = get_region_list();
        $this->assign('region_list', $region_list);
        $this->assign('lists', $address_lists);
        $this->assign('active', 'address_list');

        $this->display();
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
        header("Content-type:text/html;charset=utf-8");
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, I('post.'));
            if ($data['status'] != 1)
                exit('<script>alert("' . $data['msg'] . '");history.go(-1);</script>');
            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province', $p);
        $this->display('edit_address');

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        header("Content-type:text/html;charset=utf-8");
        $id = I('get.id');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($data['status'] != 1)
                exit('<script>alert("' . $data['msg'] . '");history.go(-1);</script>');

            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        //获取省份
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $c = M('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
        $d = M('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
        if ($address['twon']) {
            $e = M('region')->where(array('parent_id' => $address['district'], 'level' => 4))->select();
            $this->assign('twon', $e);
        }

        $this->assign('province', $p);
        $this->assign('city', $c);
        $this->assign('district', $d);
        $this->assign('address', $address);
        $this->display();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default()
    {
        $id = I('get.id');
        M('user_address')->where(array('user_id' => $this->user_id))->save(array('is_default' => 0));
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->save(array('is_default' => 1));
        if (!$row)
            $this->error('操作失败');
        $this->success("操作成功");
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
        $id = I('get.id');

        $address = M('user_address')->where("address_id = $id")->find();
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address = M('user_address')->where("user_id = {$this->user_id}")->find();
            if (!empty($address)) {
                M('user_address')->where("address_id = {$address['address_id']}")->save(array('is_default' => 1));
            }
        }
        if (!$row)
            $this->error('操作失败', U('/Home/User/address_list'));
        else
            $this->success("操作成功", U('/Home/User/address_list'));
    }

    /*
     * 评论晒单
     */
    public function comment()
    {
        $user_id = $this->user_id;
        $status = I('get.status', 0);
        $logic = new UsersLogic();
        $data = $logic->get_comment($user_id, $status); //获取评论列表
        /*$count_comment = 0;
        foreach ($data['result'] as $item) {
            if($item['is_comment'] == 0){
                $count_comment += 1;
            }
        }*/
        $this->assign('page', $data['show']);// 赋值分页输出
        $this->assign('comment_list', $data['result']);
        $this->assign('status', $status);
        // $this->assign('count_comment',$count_comment);
        $this->assign('active', 'comment');
        $this->display();
    }

    /*
  *添加评论页面
  */
    public function comment_review()
    {
        $this->assign('active', 'comment');
        $this->display();
    }

    /*
    *添加评论
    */
    public function add_comment()
    {
        if (IS_POST) {
            // 晒图片
            if ($_FILES[comment_img_file][tmp_name][0]) {
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = $map['author'] = (1024 * 1024 * 3);// 设置附件上传大小 管理员10M  否则 3M
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath = './Public/upload/comment/'; // 设置附件上传根目录
                $upload->replace = true; // 存在同名文件是否是覆盖，默认为false
                //$upload->saveName  =  'file_'.$id; // 存在同名文件是否是覆盖，默认为false
                // 上传文件
                $upinfo = $upload->upload();
                if (!$upinfo) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                } else {
                    foreach ($upinfo as $key => $val) {
                        $comment_img[] = '/Public/upload/comment/' . $val['savepath'] . $val['savename'];
                    }
                    $add['img'] = serialize($comment_img); // 上传的图片文件
                }
            }
            $user_info = session('User');
            $logic = new UsersLogic();
            $add['goods_id'] = I('goods_id');
            $add['email'] = $user_info['email'];
            $hide_username = I('hide_username');
            if (empty($hide_username)) {
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
            if ($row[status] == 1) {
                $this->success('评论成功', U('/Home/Goods/goodsInfo', array('id' => $add['goods_id'])));
                exit();
            } else {
                $this->error($row[msg]);
            }
        }
        $rec_id = I('id');
//        var_dump($rec_id);
        $order_goods = M('order_goods')->where("goods_id = $rec_id")->find();
//        show_bug($order_goods);
        $this->assign('active', 'comment');
        $this->assign('order_goods', $order_goods);
        $this->display();
    }
//    /*
//     *添加评论
//     */
//    public function add_comment()
//    {
//        $user_info = session('User');
//        $comment_img = serialize(I('comment_img')); // 上传的图片文件
//        $add['goods_id'] = I('goods_id');
//        $add['email'] = $user_info['email'];
//        //$add['nick'] = $user_info['nickname'];
//        $add['username'] = $user_info['nickname'];
//        $add['order_id'] = I('order_id');
//        $add['service_rank'] = I('service_rank');
//        $add['deliver_rank'] = I('deliver_rank');
//        $add['goods_rank'] = I('goods_rank');
//        //$add['content'] = htmlspecialchars(I('post.content'));
//        $add['content'] = I('content');
//        $add['img'] = $comment_img;
//        $add['add_time'] = time();
//        $add['ip_address'] = $_SERVER['REMOTE_ADDR'];
//        $add['user_id'] = $this->user_id;
//        $logic = new UsersLogic();
//        //添加评论
//        $row = $logic->add_comment($add);
//        exit(json_encode($row));
//    }

    /*
     * 个人信息
     */
    public function info()
    {
        $userLogic = new UsersLogic();
//        show_bug($_SERVER);
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $inviteid = sprintf("%06d", $user_info['user_id']);
        $inviteurl = $_SERVER['HTTP_HOST'] . U('reg', array('invite' => $inviteid));
        $this->assign('inviteurl', $inviteurl);
        $this->assign('User', $user_info);
        $this->assign('active', 'info');
        $this->display();
    }

    /**
     * [info_edit 修改个人信息]
     * @return [type] [description]
     */
    public function info_edit()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if (IS_POST) {
            $post = I('post.');
            //真实姓名
            if ($user_info['name'] && $post['name']) {
                if ($user_info['name'] != $post['name']) {
                    $this->error("保存失败，真实姓名不能更改");
                    die;
                }
            }
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.weixin') ? $post['weixin'] = I('post.weixin') : false;  //微信
            I('post.weibo') ? $post['weibo'] = I('post.weibo') : false;  //新浪微博
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : false;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            I('post.adderss') ? $post['adderss'] = I('post.adderss') : false;  //详细地址
            I('post.email') ? $post['email'] = I('post.email') : false;  //email
            I('post.marry') ? $post['marry'] = strtotime(I('post.marry')) : false;  //结婚纪念日
            unset($post['__hash__']);
            // print_r($post);die;
            if (!$userLogic->update_info($this->user_id, $post)) {
                $this->error("保存失败");
                die;
            }
            $this->success("操作成功");
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //  获取订单城市
        $city = M('region')->where(array('parent_id' => $user_info['province'], 'level' => 2))->select();
        //获取订单地区
        $area = M('region')->where(array('parent_id' => $user_info['city'], 'level' => 3))->select();

        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('User', $user_info);
        // $this->assign('sex',C('SEX'));
        $this->assign('active', 'info');
        $this->display();
    }

    /*
     * 邮箱验证
     */
    public function email_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        if (IS_POST) {
            $email = I('post.email');
            $old_email = I('post.old_email'); //旧邮箱
            $code = I('post.code');
            $info = session('validate_code');
            if (!$info)
                $this->error('非法操作', U('Home/User/email_validate'));
            //检查原邮箱是否正确
            if ($user_info['email_validated'] == 1 && $old_email != $user_info['email'])
                $this->error('原邮箱匹配错误', U('Home/User/email_validate'));
            //验证邮箱和验证码
            if ($info['sender'] == $email && $info['code'] == $code) {
                session('validate_code', null);
                if (!$userLogic->update_email_mobile($email, $this->user_id))
                    $this->error('邮箱已存在', U('Home/User/email_validate'));
                $this->assign('active', "safe");
                $this->display('email_success');
                // $this->success('绑定成功',U('Home/User/index'));
                exit;
            }
            $this->error('邮箱验证码不匹配', U('Home/User/email_validate'));
        }
        $mail_time_out = 60;
        $this->assign('step', $step);
        $this->assign('mail_time_out', $mail_time_out);
        $this->assign('active', 'safe');
        $this->display();
    }


    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); //获取用户信息
        $user_info = $user_info['result'];
        $config = F('sms', '', TEMP_PATH);
        $sms_time_out = $config['sms_time_out'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $mobile = I('post.mobile');
            $old_mobile = I('post.old_mobile');
            $code = I('post.code');
            $info = session('validate_code');
            if (!$info)
                $this->error('非法操作', U('Home/User/mobile_validate'));
            //检查原手机是否正确
            if ($user_info['mobile_validated'] == 1 && $old_mobile != $user_info['mobile'])
                $this->error('原手机号码错误', U('Home/User/mobile_validate'));
            //验证手机和验证码
            if ($info['sender'] == $mobile && $info['code'] == $code) {
                session('mobile_code', null);
                //验证有效期
                if ($info['time'] < time())
                    $this->error('验证码已失效', U('Home/User/mobile_validate'));
                if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                    $this->error('手机已存在', U('Home/User/mobile_validate'));
                $this->assign('active', "safe");
                $this->display('mobile_success');
                // $this->success('绑定成功',U('Home/User/index'));
                exit;
            }
            $this->error('手机验证码不匹配', U('Home/User/mobile_validate'));
        }
        $info = session('validate_code');
        //是否已发送过验证码
        if ($info) {
            $wait = $info['time'] - time();  //倒计时剩余秒数
            $wait = $wait > 0 ? $wait : 0;
        }
        $this->assign('time', $sms_time_out);
        $this->assign('wait', $wait);
        $this->assign('mobile', $info['sender']);
        $this->assign('active', "safe");
        $this->assign('step', $step);
        $this->display();
    }

    /**
     * 发送手机注册验证码
     */
    public function send_sms_reg_code()
    {
        $mobile = I('mobile');
        $userLogic = new UsersLogic();
        if (!check_mobile($mobile))
            exit(json_encode(array('status' => -1, 'msg' => '手机号码格式有误')));
        $code = rand(1000, 9999);
        $send = $userLogic->sms_log($mobile, $code, $this->session_id);
        if ($send['status'] != 1) {
            exit(json_encode(array('status' => -1, 'msg' => $send['msg'])));
        } else {
            exit(json_encode(array('status' => 1, 'msg' => '验证码已发送，请注意查收')));
        }
    }

    /*
     *商品收藏
     */
    public function goods_collect()
    {
        $goodsAttr = M('GoodsAttr');
        $userLogic = new UsersLogic();
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $data = $userLogic->get_goods_collect($this->user_id);
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        foreach ($data['result'] as &$good) {
            // $good['filter_spec'] = $goodsLogic->get_spec($good['goods_id']); //规格
            $good['goods_attr_list'] = $goodsAttr->where("goods_id = {$good['goods_id']}")->select(); // 查询商品属性表
        }
        // print_r($goods_attribute);
        // print_r($data);die;
        $this->assign('page', $data['show']);// 赋值分页输出
        $this->assign('lists', $data['result']);
        $this->assign('goods_attribute', $goods_attribute);
        $this->assign('active', 'goods_collect');
        $this->display();
    }

    /*
     * 删除一个收藏商品
     */
    public function del_goods_collect()
    {
        $id = I('get.id');
        if (!$id)
            $this->error("缺少ID参数");
        $row = M('goods_collect')->where(array('collect_id' => $id, 'user_id' => $this->user_id))->delete();
        if (!$row)
            $this->error("删除失败");
        $this->success('删除成功');
    }

    /*
     * 密码修改
     */
    public function password()
    {
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if ($user['mobile'] == '' && $user['email'] == '')
            $this->error('请先绑定手机或邮箱', U('Home/User/info'));
        if (IS_POST) {
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id, I('post.old_password'), I('post.new_password'), I('post.confirm_password')); // 获取用户信息
            if ($data['status'] == -1) {
                $this->error($data['msg']);
                die;
            }
            $this->success($data['msg']);
            exit;
        }
        $this->assign('active', 'safe');
        $this->display();
    }

    public function forget_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        if (IS_POST) {
            $logic = new UsersLogic();
            $username = I('post.username');
            $code = I('post.code');
            $new_password = I('post.new_password');
            $confirm_password = I('post.confirm_password');
            $pass = false;

            //检查是否手机找回
            if (check_mobile($username)) {
                if (!$user = get_user_info($username, 2))
                    $this->error('账号不存在');
                $check_code = $logic->sms_code_verify($username, $code, $this->session_id);
                if ($check_code['status'] != 1)
                    $this->error($check_code['msg']);
                $pass = true;
            }
            //检查是否邮箱
            if (check_email($username)) {
                if (!$user = get_user_info($username, 1))
                    $this->error('账号不存在');
                $check = session('forget_code');
                if (empty($check))
                    $this->error('邮箱验证码不存在');
                if (!$username || !$code || $check['email'] != $username || $check['code'] != $code)
                    $this->error('邮箱验证码不匹配');
                $pass = true;
            }
            if ($user['user_id'] > 0 && $pass)
                $data = $logic->password($user['user_id'], '', $new_password, $confirm_password, false); // 获取用户信息
            if ($data['status'] != 1)
                $this->error($data['msg'] ? $data['msg'] : '操作失败');
            $this->success($data['msg'], U('Home/User/login'));
            exit;
        }
        $this->display();
    }

    public function set_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        $check = session('validate_code');
        if (empty($check)) {
            header("Location:" . U('Home/User/forget_pwd'));
        } elseif ($check['is_check'] == 0) {
            $this->error('验证码还未验证通过', U('Home/User/forget_pwd'));
        }
        if (IS_POST) {
            $password = I('post.password');
            $password2 = I('post.password2');
            if ($password2 != $password) {
                $this->error('两次密码不一致', U('Home/User/forget_pwd'));
            }
            if ($check['is_check'] == 1) {
                //$User = get_user_info($check['sender'],1);
                $user = M('users')->where("mobile = '{$check['sender']}' or email = '{$check['sender']}'")->find();
                M('users')->where("user_id=" . $user['user_id'])->save(array('password' => encrypt($password)));
                session('validate_code', null);
                header("Location:" . U('Home/User/finished'));
            } else {
                $this->error('验证码还未验证通过', U('Home/User/forget_pwd'));
            }
        }
        $this->display();
    }

    public function finished()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        $this->display();
    }

    public function check_captcha()
    {
        $verify = new Verify();
        $type = I('post.type', 'user_login');

        if (!tpCache('verify_code.forget_show')) {
            if (!$verify->check(I('post.verify_code'), $type)) {
                exit(json_encode(0));
            } else {
                exit(json_encode(1));
            }
        } else {
            exit(json_encode(1));
        }

    }

    public function check_username()
    {
        $username = I('post.username');
        if (!empty($username)) {
            $count = M('users')->where("email='$username' or mobile='$username'")->count();
            exit(json_encode(intval($count)));
        } else {
            exit(json_encode(0));
        }
    }

    public function identity()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('Home/User/Index'));
        }
        $username = I('post.username');
        $userinfo = array();
        if ($username) {
            $userinfo = M('users')->where("email='$username' or mobile='$username'")->find();
            $userinfo['username'] = $username;
            session('userinfo', $userinfo);
        } else {
            $this->error('参数有误！！！');
        }
        if (empty($userinfo)) {
            $this->error('非法请求！！！');
        }
        unset($user_info['password']);
        // 手机短信超时时间
        $sms_time_out = tpCache('sms.sms_time_out') > 0 ? tpCache('sms.sms_time_out') : 120;
        //是否已发送过验证码
        $wait = 0;  //倒计时剩余秒数
        $code = M('sms_log')->where("mobile = '$username'")->order('id desc')->find();
        if ($code) {
            $wait = $sms_time_out - (time() - $code['add_time']);
            $wait = $wait > 0 ? $wait : 0;
        }
        $this->assign('sms_time_out', $sms_time_out); //手机短信超时时间
        $this->assign('wait', $wait); // 短信验证码倒计时秒数
        $this->assign('userinfo', $userinfo);
        $this->display();
    }

    //发送验证码
    public function send_validate_code()
    {
        $type = I('type');
        $send = I('send');
        $logic = new UsersLogic();
        $logic->send_validate_code($send, $type);
    }

    public function check_validate_code()
    {
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
        if (!tpCache('verify_code.reg_show')) {
            $verify = new Verify();
            if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
                $this->error("验证码错误");
            }
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

    public function order_confirm()
    {
        $id = I('get.id', 0);
        $data = confirm_order($id);
        if (!$data['status'])
            $this->error($data['msg']);
        else
            $this->success($data['msg']);
    }

    /**
     * 申请退货
     */
    public function return_goods()
    {
        $order_id = I('order_id', 0);
        $order_sn = I('order_sn', 0);
        $goods_id = I('goods_id', 0);
        $spec_key = I('spec_key');
        $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id and status in(0,1) and spec_key = '$spec_key'")->find();
        if (!empty($return_goods)) {
            $this->success('已经提交过退货申请!', U('Home/User/return_goods_info', array('id' => $return_goods['id'])));
            exit;
        }
        if (IS_POST) {
            $data['order_id'] = $order_id;
            $data['order_sn'] = $order_sn;
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            $data['user_id'] = $this->user_id;
            $data['type'] = I('type'); // 服务类型  退货 或者 换货
            $data['reason'] = I('reason'); // 问题描述
            $data['imgs'] = I('imgs'); // 用户拍照的相片
            $data['spec_key'] = I('spec_key'); // 商品规格
            M('return_goods')->add($data);
            $this->success('申请成功,客服第一时间会帮你处理', U('Home/User/order_list'));
            exit;
        }

        $goods = M('goods')->where("goods_id = $goods_id")->find();
        $this->assign('goods', $goods);
        $this->assign('order_id', $order_id);
        $this->assign('order_sn', $order_sn);
        $this->assign('goods_id', $goods_id);
        $this->display();
    }

    /**
     * 退换货列表
     */
    public function return_goods_list()
    {
        $return_type = C('RETURN_TYPE');
        $count = M('return_goods')->where("user_id = {$this->user_id}")->count();
        $page = new Page($count, 10);
        $list = M('return_goods')->where("user_id = {$this->user_id}")->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        foreach ($list as $k => $v) {
            $map['order_sn'] = $v['order_sn'];
            $map['addtime'] = array('lt', $v['addtime']);
            $l = M('return_goods')->where($map)->select();
            $str = array();
            foreach ($l as $k1 => $v1) {
                $str[$k1]['typename'] = $return_type[$v1['type']];
                $str[$k1]['date'] = date("Y-m-d", $v1['addtime']);
            }
            $list[$k]['log'] = $str;
            $list[$k]['typename'] = $return_type[$v['type']];
        }
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if (!empty($goods_id_arr))
            $goodsList = M('goods')->where("goods_id in (" . implode(',', $goods_id_arr) . ")")->getField('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('active', 'return');
        $this->display();
    }

    /**
     * 退换货申请
     */
    public function return_goods_submit()
    {
        $where = ' user_id=' . $this->user_id;
        $where .= " AND order_status in (2,4)";
        $order_list = M('order')->order("order_id DESC")->where($where)->select();
        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
//        show_bug($order_list);

        $return_type = C('RETURN_TYPE');
        $count = M('return_goods')->where("user_id = {$this->user_id}")->count();
        $page = new Page($count, 10);
        $list = M('return_goods')->where("user_id = {$this->user_id}")->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        foreach ($list as $k => $v) {
            $map['order_sn'] = $v['order_sn'];
            $map['addtime'] = array('lt', $v['addtime']);
            $l = M('return_goods')->where($map)->select();
            $str = array();
            foreach ($l as $k1 => $v1) {
                $str[$k1]['typename'] = $return_type[$v1['type']];
                $str[$k1]['date'] = date("Y-m-d", $v1['addtime']);
            }
            $list[$k]['log'] = $str;
            $list[$k]['typename'] = $return_type[$v['type']];
        }
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if (!empty($goods_id_arr))
            $goodsList = M('goods')->where("goods_id in (" . implode(',', $goods_id_arr) . ")")->getField('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $this->assign('lists', $order_list);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('active', 'return_submit');
        $this->display();
    }

    /**
     *  退货详情
     */
    public function save_return()
    {
        $order_id = I('order_id', 0);
        $return_goods = M('order')->where("order_id = $order_id")->find();
        $order_sn = $return_goods['order_sn'];
        $goods_id = I('goods_id', 0);
        $spec_key = I('spec_key');
        $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id and status in(0,1) and spec_key = '$spec_key'")->find();
        if (!empty($return_goods)) {
            $this->success('已经提交过售后申请，等待客服处理后再申请！', U('Home/User/return_goods_submit', array('id' => $return_goods['id'])));
            exit;
        }
        if (IS_POST) {
            $data['order_id'] = $order_id;
            $data['order_sn'] = $order_sn;
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            $data['user_id'] = $this->user_id;
            $data['type'] = I('type'); // 服务类型  退货 或者 换货
            $data['reason'] = I('reason'); // 问题描述
            $data['imgs'] = I('imgs'); // 用户拍照的相片
            $data['spec_key'] = I('spec_key'); // 商品规格
            M('return_goods')->add($data);
            $this->success('申请成功,客服第一时间会帮你处理', U('Home/User/return_goods_submit'));
            exit;
        }


    }

    public function return_goods_info()
    {
        $id = I('id', 0);
        $return_goods = M('return_goods')->where("id = $id")->find();
        if ($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $goods = M('goods')->where("goods_id = {$return_goods['goods_id']} ")->find();
        $this->assign('goods', $goods);
        $this->assign('return_goods', $return_goods);
        $this->display();
    }

    /**
     * 安全设置
     */
    public function safety_settings()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $this->assign('User', $user_info);
        $this->assign('active', 'safe');
        $this->display();
    }

    public function integral_change()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $log1 = M('AccountLog')->where(array('user_id' => $this->user_id, 'status' => 1))->order('change_time desc')->select();//获取
        $log2 = M('AccountLog')->where(array('user_id' => $this->user_id, 'status' => 2))->order('change_time desc')->select();//消费

        $this->assign('log1', $log1);
        $this->assign('log2', $log2);
        $user_info = $user_info['result'];

        $this->assign('User', $user_info);
        $this->assign('active', 'change');
        $this->display();
    }

    public function integral_rule()
    {


        $info = M('user_rule')->find();
        $info['rule'] = htmlspecialchars_decode($info['rule']);
        $this->assign('info', $info);
        $this->assign('active', 'rule');
        $this->display();
    }

    public function recharge()
    {
        $this->assign('active', 'recharge');
        if (IS_POST) {
            $user = session('user');
            $data['user_id'] = $this->user_id;
            $data['nickname'] = $user['nickname'];
            $data['account'] = I('account');
            $data['order_sn'] = 'recharge' . get_rand_str(10, 0, 1);
            $data['ctime'] = time();
            $order_id = M('recharge')->add($data);
            if ($order_id) {
                $url = U('Payment/getPay', array('pay_radio' => $_REQUEST['pay_radio'], 'order_id' => $order_id));
                redirect($url);
            } else {
                $this->error('提交失败,参数有误!');
            }
        }

        $paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and  scene in(0,2)")->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
//        show_bug($bankCodeList);
        $bank_img = include 'Application/Home/Conf/bank.php'; // 银行对应图片
        $this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
        $this->assign('bankCodeList', $bankCodeList);

        $count = M('recharge')->where(array('user_id' => $this->user_id))->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $recharge_list = M('recharge')->where(array('user_id' => $this->user_id))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('recharge_list', $recharge_list);//充值记录

        $count2 = M('account_log')->where(array('user_id' => $this->user_id, 'user_money' => array('gt', 0)))->count();
        $Page2 = new Page($count2, 10);
        $consume_list = M('account_log')->where(array('user_id' => $this->user_id, 'user_money' => array('gt', 0)))->limit($Page2->firstRow . ',' . $Page2->listRows)->select();
        $this->assign('consume_list', $consume_list);//消费记录
        $this->assign('page2', $Page2->show());
        $this->display();
    }

    public function fenqi()
    {
        $statusname = array('审核中', '通过', '未通过');
        $list = M('fenqi')->where(array('user_id' => $this->user_id))->select();
        foreach ($list as $k => $v) {
            $list[$k]['statusname'] = $statusname[$v['status']];
            $goods = M('goods')->find($v['goods_id']);
            $list[$k]['goods_name'] = $goods['goods_name'];
        }


        $this->assign('list', $list);
        $this->assign('active', 'fenqi');
        $this->display();
    }

    public function message()
    {

        $this->assign('active', 'message');
//        C('TOKEN_ON',true);
        if (IS_POST) {

            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $user = session('User');
            $data['user_name'] = $user['nickname'];
            $data['msg_time'] = time();
            if (M('feedback')->add($data)) {
                $this->success("留言成功", U('User/message'));
                exit;
            } else {
                $this->error('留言失败', U('User/message'));
                exit;
            }
        }
        $msg_type = array(0 => '商品咨询', 1 => '库存及配送', 2 => '支付问题', 3 => '发票及保修', 4 => '促销及赠品');
        $count = M('feedback')->where("user_id=" . $this->user_id)->count();
        $Page = new Page($count, 8);

        $Page->setConfig('prev', '&nbsp;');
        $Page->setConfig('next', '&nbsp;');

//        $Page->rollPage = 2;
        $message = M('feedback')->where("user_id=" . $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($message as $k => $v) {
            $map['parent_id'] = $v['msg_id'];
            $result = M('feedback')->where($map)->select();
            $message[$k]['admin'] = $result;
        }
//        show_bug($message);
        $showpage = $Page->show();
//        header("Content-type:text/html;charset=utf-8");

        $this->assign('page', $showpage);
        $this->assign('message', $message);
        $this->assign('msg_type', $msg_type);
        $this->display();

    }


    public function myson()
    {
        $this->assign('active', 'myson');
        $count = M('users')->where("invite_id=" . $this->user_id)->count();
        $Page = new Page($count, 8);
        $Page->setConfig('prev', '&nbsp;');
        $Page->setConfig('next', '&nbsp;');
        $list = M('users')->where("invite_id=" . $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $showpage = $Page->show();
        $this->assign('page', $showpage);
        $this->assign('list', $list);

        $this->display();

    }

    public function invite()
    {
        $this->assign('active', 'invite');
        $count = M('account_log')->where("type = 1 and is_now=1 and  user_id=" . $this->user_id)->count();
        $Page = new Page($count, 8);
        $Page->setConfig('prev', '&nbsp;');
        $Page->setConfig('next', '&nbsp;');
        $list = M('account_log')->where("type = 1 and is_now=1 and user_id=" . $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            $list[$k]['next_nickname'] = M('users')->where(array('user_id' => $v['sid']))->getField('nickname');
        }
        $showpage = $Page->show();
        $this->assign('page', $showpage);
        $this->assign('list', $list);

        $this->display();

    }

    public function commission()
    {
        $this->assign('active', 'commission');
        $count = M('account_log')->where("type = 1 and is_now=1 and user_id=" . $this->user_id)->count();
        $Page = new Page($count, 8);
        $Page->setConfig('prev', '&nbsp;');
        $Page->setConfig('next', '&nbsp;');
        $list = M('account_log')->where("type = 1 and is_now=1 and user_id=" . $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            $list[$k]['next_nickname'] = M('users')->where(array('user_id' => $v['sid']))->getField('nickname');
        }
        $showpage = $Page->show();
        $this->assign('page', $showpage);
        $this->assign('list', $list);

        $this->display();

    }
}