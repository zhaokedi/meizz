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
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace Mobile\Controller;
class CartController extends MobileBaseController {
    
    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();        
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();                
        $this->cartLogic = new \Home\Logic\CartLogic();                 
        if(session('?User'))
        {
        	$user = session('User');
                $user = M('users')->where("user_id = {$user['user_id']}")->find();
                session('User',$user);  //覆盖session 中的 User
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('User',$user); //存储用户信息
                // 给用户计算会员价 登录前后不一样
                if($user)
                    M('Cart')->execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$this->session_id}') and prom_type = 0");                
        }            
    }
//    public function index1()
//    {
//        send_email1('3048327507@qq.com','qwe','qwew');
//    }

    public function cart(){
        $this->display('cart');
    }
    /**
     * 将商品加入购物车
     */
    function addCart()
    {
        $goods_id = I("goods_id"); // 商品id
        $goods_num = I("goods_num");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格                
        $goods_spec = json_decode($goods_spec,true); //app 端 json 形式传输过来
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I("user_id",0); // 用户id        
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$unique_id,$user_id); // 将商品加入购物车
        exit(json_encode($result)); 
    }
    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id"); // 商品id
        $goods_num = I("goods_num");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id); // 将商品加入购物车
        exit(json_encode($result));
    }

    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {
        $cart_form_data = $_POST["cart_form_data"]; // goods_num 购物车商品数量
        $cart_form_data = json_decode($cart_form_data,true); //app 端 json 形式传输过来

        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I("user_id"); // 用户id
        $where = " session_id = '$unique_id' "; // 默认按照 $unique_id 查询
        $user_id && $where = " user_id = ".$user_id; // 如果这个用户已经等了则按照用户id查询
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected");

        if($cart_form_data)
        {
            // 修改购物车数量 和勾选状态
            foreach($cart_form_data as $key => $val)
            {
                $data['goods_num'] = $val['goodsNum'];
                $data['selected'] = $val['selected'];
                $cartID = $val['cartID'];
                if(($cartList[$cartID]['goods_num'] != $data['goods_num']) || ($cartList[$cartID]['selected'] != $data['selected']))
                    M('Cart')->where("id = $cartID")->save($data);
            }
            //$this->assign('select_all', $_POST['select_all']); // 全选框
        }

        $result = $this->cartLogic->cartList($this->user, $unique_id,0);
        exit(json_encode($result));
    }

    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {

        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
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

//        if($this->cartLogic->cart_count($this->user_id,1) == 0 )
//            $this->error ('你的购物车没有选中商品','Cart/cart');

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司

        $Model = new \Think\Model(); // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $sql = "select c1.card_title,c1.kqcontent,c1.end_timestamp,c1.kqexpand,c2.* from __PREFIX__cashier_wxcoupon as c1 inner join __PREFIX__cashier_wxcoupon_receive as c2  on c2.cid = c1.id and c1.send_type in(0,1,2) and c2.status=0  where c2.openid = '{$this->user['openid']}' and ".time()." < c1.end_timestamp ";
        $couponList = $Model->query($sql);
        foreach($couponList as $k=>$v){
            $couponList[$k]['kqcontent']=unserialize($v['kqcontent']);
            $couponList[$k]['kqexpand']=unserialize($v['kqexpand']);

        }
//        show_bug($v['kqexpand']);
        $empty='<p style="font-size: 25px;position: absolute;margin:40% 10%">您没有可以使用的优惠券</p>';
        $this->assign('empty',$empty);
        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        $this->display();
    }
    public function cart2mm()
    {

        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
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

//        if($this->cartLogic->cart_count($this->user_id,1) == 0 )
//            $this->error ('你的购物车没有选中商品','Cart/cart');

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司

        $Model = new \Think\Model(); // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $sql = "select c1.name,c1.money,c1.condition, c2.* from __PREFIX__coupon as c1 inner join __PREFIX__coupon_list as c2  on c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0  where c2.uid = {$this->user_id} and ".time()." < c1.use_end_time and c1.condition <= {$result['total_price']['total_fee']}";
        $couponList = $Model->query($sql);

        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        $this->display();
    }
    public function gift(){
        $id=I('gift_id');
        $gift=M('gift_pack')->find($id);
        if($gift['goods_num']>0 && $gift['order_num']>=$gift['goods_num']){
            $this->ajaxReturn(array('status'=>-1,'msg'=>'商品库存不足'));
        }
        $this->ajaxReturn(array('status'=>1));
    }
    public function gift_submit(){
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
//        $spec_key=I('spec_key');
//        $spec_key_name=I('spec_key_name');
        $address_id = I('address_id');
        if($address_id)
            $address = M('user_address')->where("address_id = $address_id")->find();
        else
            $address = M('user_address')->where("user_id = $this->user_id and is_default=1")->find();
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'gift_submit')));
        }else{
            $this->assign('address',$address);
        }
        $gift_id=I('gift_id');
        $gift=M('gift_pack')->where(array('id'=>$gift_id))->find();
        $gift['goods_id']=explode("|",$gift['goods_id']);
        $gift['goods_name']=explode("|",$gift['goods_name']);
//show_bug($gift['goods_id'][0]);
//        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司
//        $this->assign('couponList', $couponList); // 优惠券列表
//        $this->assign('spec_key', $spec_key); // 物流公司
//        $this->assign('spec_key_name', $spec_key_name); // 物流公司
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('goods', $gift); // 购物车的商品
//        show_bug($goods);
//        $this->assign('total_price', $result['total_price']); // 总计
        $this->display();
    }
    public function gift_submit1(){
        if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        $address_id = I("address_id"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号
//            $invoice_title = I('invoice_title'); // 发票
//            $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
//            $coupon_id =  I("coupon_id"); //  优惠券id
//            $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("price",0); //  使用积分
        $goods_id =  I("gift_id",0); //  使用积分
        $goods_num =  I("num",1); //  使用积分
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
                'invoice_title'    =>I('invoice_title'), //'发票抬头',
                'goods_price'      =>0.01,//'商品价格',
                'shipping_price'   =>0,//'物流价格',
                'user_money'       =>0,//'使用余额',
                'coupon_price'     =>0,//'使用优惠券',
                'integral_money'   =>0,//'使用积分抵多少钱',
                'gift_id'          =>$goods_id,
                'is_gift'=>1,//是否为积分商品
                'pay_status'=>0,
                'total_amount'     =>$pay_points,// 订单总额
                'order_amount'     =>$pay_points,//'应付款金额',
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
//            update_pay_status($order['order_sn'], 1);
//            $goods = M('goods')->where("goods_id = $goods_id ")->find();
//            $data2['order_id']           = $order_id; // 订单id
//            $data2['goods_id']           = $goods_id; // 商品id
//            $data2['goods_name']         = $goods['goods_name']; // 商品名称
//            $data2['goods_sn']           = $goods['goods_sn']; // 商品货号
//            $data2['goods_num']          = 1; // 购买数量
//            $data2['market_price']       = $goods['market_price']; // 市场价
//            $data2['goods_price']        = 0.01; // 商品价
//            $data2['integral_price']        = $goods['integral_price']; // 商品价
//            $data2['spec_key']           = I('spec_key'); // 商品规格
//            $data2['spec_key_name']      = I('spec_key_name'); // 商品规格名称
//            $data2['sku']           = $goods['sku']; // 商品sku
//            $data2['member_goods_price'] = 0; // 会员折扣价
//            $data2['cost_price']         = $goods['cost_price']; // 成本价
//            $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分
//            $data2['prom_type']          = 0; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
//            $data2['prom_id']            = 0; // 活动id
//            $order_goods_id              = M("OrderGoods")->data($data2)->add();
            M('gift_pack')->where("id = ".$goods_id)->setInc('buy_num',1);//扣除库存
//                $result = $this->cartLogic->addOrder($this->user_id,$address_id,$shipping_code,'no',0,$car_price); // 添加订单
//                $order = M('Order')->where(array('order_id'=>$result['result']))->find();
//                $content = "美之钻订单提交成功"."<br>订单金额:".$car_price['payables']."<br>订单编号:".$order['order_sn']."<br>联系方式:".$address['mobile']."<br>联系人:".$address['consignee']."<br>下单时间:".date("Y-m-d H:i:s");//发送邮件提醒
//                send_email1('美之钻下单提醒',$content);
            exit(json_encode(array('status'=>1,'msg'=>'提交成功','result'=>$order_id)));
        }
//            $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
//            exit(json_encode($return_arr));
    }
    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
                                
        if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        
        $address_id = I("address_id"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号        
        $invoice_title = I('invoice_title'); // 发票
        $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id =  I("coupon_id"); //  优惠券id
        $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("pay_points",0); //  使用积分
        $user_money =  I("user_money",0); //  使用余额
        $heka['ptaddress']=I("ptaddress");
        $heka['hkcontent']=I("hkcontent");
        $user_money = $user_money ? $user_money : 0;

        if($this->cartLogic->cart_count($this->user_id,1) == 0 ) exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
        if(!$shipping_code) exit(json_encode(array('status'=>-4,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态
		
		$address = M('UserAddress')->where("address_id = $address_id")->find();
		$order_goods = M('cart')->where("user_id = {$this->user_id} and selected = 1")->select();
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,0,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$couponCode);
                
		if($result['status'] < 0)	
			exit(json_encode($result));      	
	// 订单满额优惠活动		                
        $order_prom = get_order_promotion($result['result']['order_amount']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;
			
        $car_price = array(
            'postFee'      => $result['result']['shipping_price'], // 物流费
            'couponFee'    => $result['result']['coupon_price'], // 优惠券            
            'balance'      => $result['result']['user_money'], // 使用用户余额
            'pointsFee'    => $result['result']['integral_money'], // 积分支付
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],// 商品价格
            'order_prom_id' => $result['result']['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱            
        );
       
        // 提交订单        
        if($_REQUEST['act'] == 'submit_order')
        {  
            if(empty($coupon_id) && !empty($couponCode))
               $coupon_id = M('cashier_wxcoupon_receive')->where("`code`='$couponCode'")->getField('id');
            if($coupon_id>0){
                $access_token=$this->get_access_token();
            }
            $result = $this->cartLogic->addOrder($heka,$this->user_id,$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price,$access_token); // 添加订单
            $order = M('Order')->where(array('order_id'=>$result['result']))->find();
            $content = "美之钻订单提交成功"."<br>订单金额:".$car_price['payables']."<br>订单编号:".$order['order_sn']."<br>联系方式:".$address['mobile']."<br>联系人:".$address['consignee']."<br>下单时间:".date("Y-m-d H:i:s");//发送邮件提醒
//              send_email1('美之钻下单提醒',$content);
            exit(json_encode($result));            
        }
            $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
            exit(json_encode($return_arr));           
    }	
    /*
     * 订单支付页面
     */
    public function cart4(){
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $isweixin=$this->isweixin();
        $this->assign('isweixin',$isweixin);

        $paymentPlugin = M('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']);
        if($isweixin){
            $jssdk = new \Mobile\Logic\Jssdk($config_value['appid'],$config_value['appsecret']);
            $signPackage = $jssdk->getSignPackage();
            $this->assign("signPackage",$signPackage);
        }

        $ischajia=0;
        $order_id = I('order_id');

        if(I('return_id')){
            $return_id=I('return_id');
            $return_goods = M('return_goods')->where("id = $return_id")->find();
            $ischajia=1;
            $this->assign('return_goods',$return_goods);

        }else{
            $order = M('Order')->where("order_id = $order_id")->find();
            // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
            if($order['pay_status'] == 1){
                $order_detail_url = U("Mobile/User/order_detail",array('id'=>$order_id));
                header("Location: $order_detail_url");
            }
            $this->assign('order',$order);
        }
        session('ischajia',$ischajia);
        $this->assign('ischajia',$ischajia);


        $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and  scene in(0,1)")->select();
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code in('weixin','cod')")->select();
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
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));

        $this->display();
    }
    public function cart5(){


        $data=I('get.');
        $url=$_SERVER['HTTP_HOST'].U('cart4',array('order_id'=>$data['order_id']));

        $this->assign('url',$url);
        $isweixin=$this->isweixin();
        $this->assign('isweixin',$isweixin);
        $paymentPlugin = M('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']);
        if($isweixin){
            $jssdk = new \Mobile\Logic\Jssdk($config_value['appid'],$config_value['appsecret']);
            $signPackage = $jssdk->getSignPackage();
            $this->assign("signPackage",$signPackage);
        }

        $order=M('order')->find($data['order_id']);
        $data['content']=$order['dfcontent'];
        $user=M('users')->find($order['user_id']);
        $this->assign('data',$data);
        $this->assign('yuser',$user);
        $this->assign('order',$order);
        $this->display();
    }
    public function daifu(){

//        show_bug($_SERVER);
//        exit;

        $data=I('get.');
        $url=$_SERVER['HTTP_HOST'].U('cart5',array('order_id'=>$data['order_id']));
        M('order')->save(array('order_id'=>$data['order_id'],'dfcontent'=>$data['content']));
        $this->assign('order',$data);
        $this->assign('url',$url);
        $isweixin=$this->isweixin();
        $paymentPlugin = M('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']);
        if($isweixin){
            $jssdk = new \Mobile\Logic\Jssdk($config_value['appid'],$config_value['appsecret']);
            $signPackage = $jssdk->getSignPackage();
            $this->assign("signPackage",$signPackage);
        }
        $this->display();
    }
    /*
    * ajax 请求获取购物车列表
    */
    public function ajaxCartList()
    {
        $post_goods_num = I("goods_num"); // goods_num 购物车商品数量
        $post_cart_select = I("cart_select"); // 购物车选中状态
        $where = " session_id = '$this->session_id' "; // 默认按照 session_id 查询
        $this->user_id && $where = " user_id = ".$this->user_id; // 如果这个用户已经等了则按照用户id查询

        $cartList = M('Cart')->where($where)->getField("id,goods_id,goods_num,selected,prom_type,prom_id");

        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {                
                $data['goods_num'] = $val < 1 ? 1 : $val;
                if($cartList[$key]['prom_type'] == 1) //限时抢购 不能超过购买数量
                {
                    $flash_sale = M('flash_sale')->where("id = {$cartList[$key]['prom_id']}")->find();
                    $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                }
                
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected']))
                    M('Cart')->where("id = $key")->save($data);
            }
            $this->assign('select_all', $_POST['select_all']); // 全选框
        }

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1);        
        if(empty($result['total_price']))
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0, 'atotal_fee' =>0, 'acut_fee' =>0, 'anum' => 0);
        
        $this->assign('cartList', $result['cartList']); // 购物车的商品                
        $this->assign('total_price', $result['total_price']); // 总计       
        $this->display('ajax_cart_list');
    }

    /*
 * ajax 获取用户收货地址 用于购物车确认订单页面
 */
    public function ajaxAddress(){

        $regionList = M('Region')->getField('id,name');

        $address_list = M('UserAddress')->where("user_id = {$this->user_id}")->select();
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('regionList', $regionList);
        $this->assign('address_list', $address_list);
        $this->display('ajax_address');
    }

    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {
        $ids = I("ids"); // 商品 ids
        $result = M("Cart")->where(" id in ($ids)")->delete(); // 删除id为5的用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态
        exit(json_encode($return_arr));
    }
    public function paysuccess(){
        $ischajia=session('ischajia');

        if(isset($ischajia) && $ischajia==1){
            $return_id=I('return_id');
            $return_goods=M('return_goods')->find(array('id'=>$return_id));
            M('return_goods')->save(array('id'=>$return_id,'is_pay'=>1));

            $data['order_id']=$return_goods['order_id'];
            $data['order_sn']=$return_goods['order_sn'];
            $data['return_id']=$return_id;
            $data['money']=$return_goods['money'];
            $data['addtime']=time();
            M('return_money_log')->add($data);


        }else{
            $daifu=I('daifu')?1:0;

            $order_id=I('order_id');
            $order = M('order')->where("order_id = '$order_id'")->find();
            if($daifu==1){
                M('order')->where("order_id = '$order_id'")->save(array('is_daifu'=>$daifu));
            }

            update_pay_status($order['order_sn']);
        }

        $this->ajaxReturn(array('status'=>1));
    }
    public function pay_info(){
        $ischajia=session('ischajia');

        if(isset($ischajia) && $ischajia==1){
            $return_id=I('return_id');
            $return_goods=M('return_goods')->where("id = '$return_id'")->find();
            $need_pay_money=$return_goods['money']*100;
            $order['order_sn']=$return_goods['order_sn'];
        }else{
            $order_id=I('order_id');
            $order = M('order')->where("order_id = '$order_id'")->find();
            $need_pay_money=$order['order_amount']*100;
        }

        $paymentPlugin = M('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化
       $appid = $config_value['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
//       $appid = 'wxeedf0b587d29acd2'; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
       $mchid = $config_value['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
//       $mchid = '1239495002'; // * MCHID：商户号（必须配置，开户邮件中可查看）
       $key = $config_value['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
//       $key = '11111111111111111111111111111111'; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）

//        $need_pay_money=$order['order_amount']*100;
        $xmlarr['appid'] = $appid;
        $xmlarr['mch_id'] = $mchid;
        $xmlarr['nonce_str'] = create_noncestr();
        $xmlarr['body'] = '美之钻订单';
        $xmlarr['out_trade_no'] =$order['order_sn'];
        $_SESSION['oid'] = $xmlarr['out_trade_no']; // 把本次生成的订单号存入session中，保存购买记录的时候用到
        $xmlarr['total_fee'] = $need_pay_money;
        $xmlarr['spbill_create_ip'] =$_SERVER["REMOTE_ADDR"];
        $xmlarr['notify_url'] = SITE_URL.'/index.php/Home/Payment/notifyUrl/pay_code/weixin';

        $xmlarr['trade_type'] = "JSAPI";
        $xmlarr['openid'] = $this->user['openid'];
        $xmlarr['sign'] = getSign($xmlarr,$key);
//        logResult1(var_export($xmlarr,true));
        $xml = "<xml>";
        $xml .= "<appid>" . $xmlarr['appid'] . "</appid>
								<body>" . $xmlarr['body']. "</body>
								<mch_id>" . $xmlarr['mch_id']. "</mch_id>
								<nonce_str>" . $xmlarr['nonce_str']. "</nonce_str>
								<notify_url>" . $xmlarr['notify_url']."</notify_url>
								<out_trade_no>" . $xmlarr['out_trade_no']. "</out_trade_no>
								<openid>".$xmlarr['openid']. "</openid>
								<spbill_create_ip>".$xmlarr['spbill_create_ip']."</spbill_create_ip>
								<total_fee>".$xmlarr['total_fee']."</total_fee>
								<trade_type>JSAPI</trade_type>
								<sign>". $xmlarr['sign']."</sign>";
        $xml .= "</xml>";

        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $response = postXmlCurl($xml, $url);

        $micropayCallResult = xmlToArray($response);

//        logResult1(var_export($micropayCallResult,true));
        if ($micropayCallResult["return_code"] == "FAIL") {
        } else if ($micropayCallResult["result_code"] == "FAIL") {
        } else if ($micropayCallResult["result_code"] == "SUCCESS" && $micropayCallResult["return_code"] == "SUCCESS") {
            $prepay_id = $micropayCallResult['prepay_id'];
        }
        if ($prepay_id) {
//            $oddata['uniacid']=$_W['uniacid'];
//            $oddata['oid']=$_SESSION['oid'];
//            $oddata['nickname']=$user_info['nickname'];
//            $oddata['uid']=$user_info['id'];
//            $oddata['addtime']=date("Y-m-d H:i:s");
//            $oddata['prepay_id']=$prepay_id;
//            $oddata['total_fee']=$need_pay_money/100;
//            $oddata['openid']=$openid;
//            $bb= pdo_insert('sf_order',$oddata);
            $tims = time();
            $nonstr = create_noncestr();
            $stringA = "appId=" .$appid . "&nonceStr=$nonstr&package=prepay_id=$prepay_id&signType=MD5&timeStamp=$tims";
            $stringB = $stringA . "&key=" . $key;


            $backdata['status'] = 1;
            $backdata['timeStamp'] = $tims;
            $backdata['nonceStr'] = $nonstr;
            $backdata['package'] = "prepay_id=$prepay_id";
            $backdata['paySign'] = strtoupper(md5($stringB));

            $this->ajaxReturn($backdata);
        }else{

            $backdata['status'] = 2;
            $backdata['msg'] = '不存在prepay_id';
            $this->ajaxReturn($backdata);
        }
    }


}
