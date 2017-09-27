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
use Home\Logic\UsersLogic;
use Think\Controller;
class MobileBaseController extends Controller {
    public $user = array();
    public $user_id = 0;
    public $session_id;
    public $weixin_config;
    public $cateTrre = array();

    /*
     * 初始化操作
     */
    public function _initialize() {

        $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机
        if(isMobile())
            cookie('is_mobile','1',3600);
        else
            cookie('is_mobile','0',3600);

        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;
            $this->assign('wechat_config', $wechat_config); // 微信配置
            if($wechat_config && !$_SESSION['openid']){
                //去授权获取openid
                $wxuser = $this->GetOpenid();
                //获取用户昵称
                session('subscribe', $wxuser['subscribe']);// 当前这个用户是否关注了微信公众号
                //微信自动登录
                $data = array(
                    'openid'=>$wxuser['openid'],//支付宝用户号
                    'oauth'=>'weixin',
                    'nickname'=>trim($wxuser['nickname']) ? trim($wxuser['nickname']) : '微信用户',
                    'sex'=>$wxuser['sex'],
                    'head_pic'=>$wxuser['headimgurl'],
                );

                $logic = new UsersLogic();
                $data = $logic->thirdLogin($data);

                if($data['status'] == 1){
                    session('User',$data['result']);
                    setcookie('user_id',$data['result']['user_id'],null,'/');
                    setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
                    setcookie('uname',$data['result']['nickname'],null,'/');

                    // 登录后将购物车的商品的 user_id 改为当前登录的id
                    M('cart')->where("session_id = '{$this->session_id}'")->save(array('user_id'=>$data['result']['user_id']));
                }
            }

            // 微信Jssdk 操作类 用分享朋友圈 JS
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();
            $this->assign('signPackage', $signPackage);
        }
        $this->public_assign();
    }
    public function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
    /**
     * 保存公告变量到 smarty中 比如 导航
     */
    public function public_assign()
    {

       $tpshop_config = array();
       $tp_config = M('config')->cache(true,TPSHOP_CACHE_TIME)->select();
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $tpshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }

       $goods_category_tree = get_goods_category_tree();
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);
       $brand_list = M('brand')->cache(true,TPSHOP_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();
       $this->assign('brand_list', $brand_list);
       $this->assign('tpshop_config', $tpshop_config);
    }
//邮件的发送方法
    public function sendemail($data){
        include 'Mail.class.php';
        $user = M('email');
        $condition['lg'] = C('lg');
        $result = $user->where($condition)->find();
        $to = $result['toemail'];

        C('MAIL_ADDRESS',$result['fromemail']);
        C('MAIL_SMTP',$result['serverhost']);
        C('MAIL_PASSWORD',$result['frompwd']);
        C('MAIL_LOGINNAME',$result['fromemail']);
        $host = $result['serverhost'];
        $fromemail = $result['fromemail'];
        $frompwd = $result['frompwd'];
        $fromname = $result['fromname'];
        $subject = $data['username'];//邮件的标题
        $content = "名字:".$data['username']."<br>邮箱:".$data['email']."<br>电话:".$data['tel']."<br>内容:".$data['content'];
        $bol = sendMail1($to,$subject,$content,$fromname);
    }
    // 网页授权登录获取 OpendId
    public function GetOpenid()
    {
        if($_SESSION['openid'])
            return $_SESSION['openid'];
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        } else {
            // 上面跳转, 这里跳了回来
            //获取code码，以获取openid
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);
            $data2 = $this->GetUserInfo($data['access_token'],$data['openid']);
            $data['nickname'] = $data2['nickname'];
            $data['sex'] = $data2['sex'];
            $data['headimgurl'] = $data2['headimgurl'];
            $data['subscribe'] = $data2['subscribe'];
            $_SESSION['openid'] = $data['openid'];
            return $data;
        }
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }

    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
         //通过code换取网页授权access_token  和 openid
        $url = $this->__CreateOauthUrlForOpenid($code);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res,true);//取出openid access_token
        curl_close($ch);

        return $data;
    }


        /**
     *
     * 通过access_token openid 从工作平台获取UserInfo
     * @return openid
     */
    public function GetUserInfo($access_token,$openid)
    {
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res,true);//取出openid access_token
        curl_close($ch);

        // 获取看看用户是否关注了 你的微信公众号， 再来判断是否提示用户 关注
        $access_token2 = $this->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/User/info?access_token=$access_token2&openid=$openid";
        $subscribe_info = httpRequest($url,'GET');
        $subscribe_info = json_decode($subscribe_info,true);
        $data['subscribe'] = $subscribe_info['subscribe'];

        return $data;
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

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["secret"] = $this->weixin_config['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    //获取微信卡券ApiTicket
    public function getcardApiTicket() {

        $data =json_decode(S("cardapi_ticket"));
        if ($data->expire_time < time()) {
            $accessToken = $this->get_access_token();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$accessToken&type=wx_card";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                $data->expire_time = time() + 7000;
                $data->jsapi_ticket = $ticket;
                S("cardapi_ticket", json_encode($data));
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }
        return $ticket;
    }
    public function sendcard($openid,$sendtype){
        $time=time();
        $wherearr = "send_type=$sendtype and end_timestamp > $time";
        $wx_coupon = M('cashier_wxcoupon')->where($wherearr)->find();
        $count=M('cashier_wxcoupon_receive')->where(array('send_type'=>1,'openid'=>$openid))->count();
        if(!empty($wx_coupon)){
            if($count==0 && $wx_coupon['quantity']> $wx_coupon['receivenum']){
                $this->send_card($openid,$wx_coupon['card_id']);
            }
        }
    }
    public function send_card($openid,$card_id){
        $api_ticket=$this->getcardApiTicket();
        $code ='';
        $timestamp =time();
        $temp= array($code, $timestamp, $card_id,$api_ticket);
        // use SORT_STRING rule
        sort($temp, SORT_STRING);
        $tmpStr = implode($temp);
        $tmpStr = sha1($tmpStr);
        $access_token=$this->get_access_token();
        $url="https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$access_token";
        $time=time();
        $json='{
                  "touser":"'.$openid.'",
                  "msgtype":"wxcard",
                  "wxcard":{
                           "card_id":"peXkJuDN78Y6Csf-7oyAoewDeHtQ",
                           "card_ext": "{"code":"","openid":"","timestamp":"'.$time.'","signature":"'.$tmpStr.'"}"
                            },
                }';
        $result=wxHttpsRequest($url,$json);
        if($result['errcode']==0 && $result['errmsg'] =='ok' ){
            return $result;
        }
        return false;
    }
    public function isweixin(){
       if( strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
           return 1;
       }else{
           return 0;
       };
    }
}