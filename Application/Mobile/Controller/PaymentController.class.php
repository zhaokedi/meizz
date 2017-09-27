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
class PaymentController extends MobileBaseController {
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();                                                  
        // tpshop 订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        if(!empty($pay_radio)) 
        {                         
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {            
            $_GET = I('get.');            
            //file_put_contents('./a.html',$_GET,FILE_APPEND);    
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }                        
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];               
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; // D:\wamp\www\svn_tpshop\www\plugins\payment\alipay\alipayPayment.class.php                       
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    }
   
    /**
     * tpshop 提交支付方式
     */
    public function getCode(){     
        
            C('TOKEN_ON',false); // 关闭 TOKEN_ON
            header("Content-type:text/html;charset=utf-8");    
        
            $order_id = I('order_id'); // 订单id
            // 修改订单的支付方式
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");                        
            M('order')->where("order_id = $order_id")->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
            
            $order = M('order')->where("order_id = $order_id")->find();
            if($order['pay_status'] == 1){
                $this->error('此订单，已完成支付!');
            }
        // tpshop 订单支付提交
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            //微信JS支付
           if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
               $code_str = $this->payment->getJSAPI($order);
               exit($code_str);
           }else{
           		$code_str = $this->payment->get_code($order,$config_value);
           }
//            logResult1(var_export($code_str,true));
            $this->assign('code_str', $code_str);
            $this->assign('order_id', $order_id); 
            $this->assign('order_sn', $order['order_sn']);
            $this->assign('pay_code', $this->pay_code);
            $this->display('payment');  // 分跳转 和不 跳转
    }

    public function getPay(){
        //手机端在线充值

        C('TOKEN_ON',false); // 关闭 TOKEN_ON
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id'); //订单id
        $user = session('User');
        $data['account'] = I('account');
        if($order_id>0){
            M('recharge')->where(array('order_id'=>$order_id,'user_id'=>$user['user_id']))->save($data);
        }else{
            $data['user_id'] = $user['user_id'];
            $data['nickname'] = $user['nickname'];
            $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
            $data['ctime'] = time();
            $order_id = M('recharge')->add($data);
        }

        if($order_id){
            $order = M('recharge')->where("order_id = $order_id")->find();
            if(is_array($order) && $order['pay_status']==0){
                $order['order_amount'] = $order['account'];
                $pay_radio = $_REQUEST['pay_radio'];
                $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数

                $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
                M('recharge')->where("order_id = $order_id")->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
                //微信JS支付
                if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
                    $code_str = $this->payment->getJSAPI($order);
                    exit($code_str);
                }else{
                    $code_str = $this->payment->get_code($order,$config_value);
                }
            }else{
                $this->error('此充值订单，已完成支付!');
            }
        }else{
            $this->error('提交失败,参数有误!');
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        $this->display('recharge'); //分跳转 和不 跳转
    }

        public function orderquery(){

            $out_trade_no=$_POST['order_sn'];
            $order = M('order')->where("order_sn = '$out_trade_no'")->find();

            if($order['pay_status']==1){
                $this->ajaxReturn(array('status'=>0,'msg'=>'订单已支付'));
            }

            $result=   $this->payment->Queryorder($_POST['order_sn']);

            if ($result){
                $order = M('order')->where("order_sn = '$out_trade_no'")->find();
                minus_stock($order['order_id']);

                $res=M('order')->where("order_sn = '$out_trade_no'")->save(array('pay_status'=>1,'pay_time'=>time()));
                $user = M('users')->where("user_id = {$order['user_id']}")->find();
                if($user['oauth']== 'weixin')
                {
                    $wx_user = M('wx_user')->find();
                    $jssdk = new \Mobile\Logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
                    $wx_content = "您的美之钻订单支付成功，我们会尽快给你发货，订单编号： {$order['order_sn']}";
                    $jssdk->push_msg($user['openid'],$wx_content);
                }
//                $content = "美之钻订单支付成功"."<br>订单金额:".$order['order_amount']."<br>订单编号:".$order['order_sn']."<br>联系方式:".$order['mobile']."<br>联系人:".$order['consignee']."<br>支付时间:".date("Y-m-d H:i:s");//发送邮件提醒
//                send_email1('美之钻支付成功提醒',$content);
                if($res){
                    $this->ajaxReturn(array('status'=>1));
                }
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'订单正在处理'));
            }
        }
        // 服务器点对点 // http://www.tp-shop.cn/index.php/Home/Payment/notifyUrl        
        public function notifyUrl(){
            $input = file_get_contents('php://input');
            logResult1(var_export($input,true));
            $this->payment->response();            
            exit();
        }

        // 页面跳转 // http://www.tp-shop.cn/index.php/Home/Payment/returnUrl        
        public function returnUrl(){
             $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';            
             $order = M('order')->where("order_sn = '{$result['order_sn']}'")->find();
             $this->assign('order', $order);
            if($result['status'] == 1)
                $this->display('success');   
            else
                $this->display('error');   
        }

    // 微信支付相关funciton
    private function create_noncestr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    /* 签名算法 */
    private function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[strtolower($k)] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        // echo "【string】 =".$String."</br>";
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . C('key');
        // echo "【string】 =".$String."</br>";
        // 签名步骤三：MD5加密
        $result_ = strtoupper(md5($String));
        return $result_;
    }
    // 把数组中的元素根据大小写进行排序,签名算法中有要求
    private function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= strtolower($k) . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
    /* 数组转换成XML格式 */
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }
    private function postXmlCurl($xml, $url)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            logResult1('Errno' . curl_error($curl)); // 捕抓异常
            exit();
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    /* xml转换成数组 */
    private function xmlToArray($xml)
    {
        // 将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

}
