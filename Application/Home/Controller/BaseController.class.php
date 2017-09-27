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
namespace Home\Controller;
use Think\Controller;
class BaseController extends Controller {
    public $session_id;
    public $cateTrre = array();
    public $tpshop_config;
    /*
     * 初始化操作
     */
    public function _initialize() {
        $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机
        if(isMobile() && !IS_AJAX && !IS_POST){
            cookie('is_mobile','1',3600);
            // 如果不是手机跳转到 手机模块
            header("Location: ".U('/Mobile'));
        }else{
            cookie('is_mobile','0',3600);
        }

        $this->public_assign();
    }
    /**
     * 保存公告变量到 smarty中 比如 导航
     */
    public function public_assign()
    {
		$now_time =  time();
        //网站配置
        $tpshop_config = array();
        $tp_config = M('config')->cache(true,TPSHOP_CACHE_TIME)->select();
        foreach($tp_config as $k => $v)
        {
            if($v['name'] == 'hot_keywords'){
                $tpshop_config['hot_keywords'] = explode('|', $v['value']);
            }
            $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
        }
        $this->tpshop_config = $tpshop_config;
        //友情链接
        $link_list = M('friend_link')->cache(true,TPSHOP_CACHE_TIME)->where("is_show = 1")->order('orderby asc')->select();
        //文章列表
        $article_cat = D('article_cat')->where("show_in_nav=1")->order("cat_id desc")->select();
        $article = D('article')->where("is_open=1 and publish_time<{$now_time}")->order("add_time desc")->select();
        foreach ($article_cat as $k => &$cat) {
            foreach ($article as $m => $art) {
                if($cat['cat_id'] == $art['cat_id']){
                    $cat['article'][] = $art;
                    unset($article[$m]);
                }
            }
        }
        //导航
        $navigation = D('navigation')->where("is_show = 1")->order('sort desc')->select();
        //商品分类
        $goods_category_tree = get_goods_category_tree();
        $this->cateTrre = $goods_category_tree;
        //商品品牌
        $brand_list = M('brand')->cache(true,TPSHOP_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();

		//IP地址
		if(empty(session('UserIp')))
		{
		   session('UserIp',getIP());
		    unset($map);
		   $map['ip']=getIP();
		   $map['city']=ip_adds(getIP());
		   $map['goin']=rtferer($_SERVER["HTTP_REFERER"]);
		   $map['add_time']=time();
		   if(rtferer($_SERVER["HTTP_REFERER"])!="self")
		   {
		      $row = M('click_log')->add($map);
		   }
		   unset($map);
		}

		//关键字
	   unset($map);
	   $kowordstr=get_keyword($_SERVER["HTTP_REFERER"]);
	   if($kowordstr!="&")
	   {
		  $kstr=explode('&', $kowordstr);
		  $keywordsql = "SELECT * from __PREFIX__keyword_log where keyword='$kstr[1]' and kurl='$kstr[0]'";
		  $new = M()->query($keywordsql);//指定日期情况
			$map['keyword']=$kstr[1];
			$map['kurl']=$kstr[0];
			$map['conuts']=1;
			$map['add_time']=time();
			$row = M('keyword_log')->add($map);
	   }
	   unset($map);
        //模板赋值
        $this->assign('goods_category_tree', $goods_category_tree);
        $this->assign('brand_list', $brand_list);
        $this->assign('navigation', $navigation);
        $this->assign('tpshop_config', $tpshop_config);
        $this->assign('link_list', $link_list);
        $this->assign('article_cat', $article_cat);
        $this->assign('user', is_login('User'));
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
    public function sendcard($openid,$sendtype){
        $time=time();

        $wherearr = "send_type=$sendtype and end_timestamp > $time";
        $wx_coupon = M('cashier_wxcoupon')->where($wherearr)->find();

        $count=M('cashier_wxcoupon_receive')->where(array('send_type'=>1,'openid'=>$openid))->count();

        if(!empty($wx_coupon)){
            if($count==0 && $wx_coupon['quantity']> $wx_coupon['receivenum']){
                $result=$this->send_card($openid,$wx_coupon['card_id']);


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
}