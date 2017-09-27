<?php

namespace Home\Controller;
use Think\Controller;
use Org\Util\wxCardPack;
class WeixinController extends BaseController {

    private $wxToken = '';
	private $mid=0;
    private $nonce = '';
    private $sTimeStamp = '';
    private $msg_signature = '';
	private $merData = '';
    public $tpshop_config;
    public function _initialize(){
        parent::_initialize();
        $this->wxToken = 'LHSistestwxToken';
		$this->mid=isset($_GET['mymid']) ? intval($_GET['mymid']) :0;

		   $this->merData = M('wx_user')->find();
		   if(empty($this->merData))  exit('User does not exist!');
		   $this->wxToken=$this->merData['w_token'];
        $this->nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $this->sTimeStamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : time();
        $this->msg_signature = isset($_REQUEST['msg_signature']) ? $_REQUEST['msg_signature'] : '';
        $this->tpshop_config = array();
        $tp_config = M('config')->select();
        foreach($tp_config as $k => $v)
        {
            $this->tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
        }
    }

    public function index() {

        if (IS_GET && isset($_GET['echostr'])) {
            $this->valid();
        } else {
            $this->responseMsg();
        }
    }

    public function valid() {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit();
        }
        exit();
    }

    private function checkSignature() {
        $signature = $_GET["signature"];
        $tmpArr = array($this->wxToken, $this->sTimeStamp, $this->nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if (trim($tmpStr) == trim($signature)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 	作用：将xml转为array
     */
    private function xmlToArray($xml) {
        //将XML转为array
        //禁止引用外部xml实体
        // libxml_disable_entity_loader(true);
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if (is_array($array_data) && !empty($array_data)) {
            foreach ($array_data as $kk => $vv) {
                if (is_array($vv)) {
                    $array_data[$kk] = !empty($vv) ? $vv : '';
                } else {
                    $array_data[$kk] = trim($vv);
                }
            }
            return $array_data;
        }
        return false;
    }

    public function responseMsg() {
        //$xmlStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $xmlStr = file_get_contents("php://input");
         /*if (empty($xmlStr)) {
          $xmlStr = '<xml> <ToUserName><![CDATA[mmmmmmm1123f]]></ToUserName>
          <FromUserName><![CDATA[fewtesGFFdsgsdgsdgdg]]></FromUserName>
          <CreateTime>123456789</CreateTime>
          <MsgType><![CDATA[event]]></MsgType>
          <Event><![CDATA[card_pass_check]]></Event>  //不通过为card_not_pass_check
          <CardId><![CDATA[pZrMmsyoBkRf57wndR9PPXumGa_4]]></CardId>
          </xml>';
          }*/
        //$logpath = './pay/wxpay/barpay/logs/wxAction.log';
        //file_put_contents($logpath, $xmlStr . "\n\n", FILE_APPEND);
        if (!empty($xmlStr)) {
            $xmlData = $this->xmlToArray($xmlStr);
//            logResult1(var_export($xmlData,true));
            if (is_array($xmlData) && !empty($xmlData)) {
                $MsgType = strtolower($xmlData['MsgType']);
                if ($MsgType == 'event') {
                    $eventVarr = array('card_pass_check', 'card_not_pass_check', 'user_get_card', 'user_del_card', 'user_consume_card', 'user_view_card');
                    $eventV = isset($xmlData['Event']) ? $xmlData['Event'] : '';
                    if ($eventV == 'card_pass_check' || $eventV == 'card_not_pass_check') {
                        $wxcouponDb = M('cashier_wxcoupon');
                        $is_pass_check = 0;/**                         * 0审核中，1审核通过2审核不通过** */
                        if ($eventV == 'card_pass_check')
                            $is_pass_check = 1;
                        if ($eventV == 'card_not_pass_check')
                            $is_pass_check = 2;
                        if ($is_pass_check > 0) {
                            $wxcouponDb->where(array('card_id' => $xmlData['CardId'],'mid'=>0))->save(array('status' => $is_pass_check, 'checktime' => time()));
                        }
                    } elseif ($eventV == 'user_get_card') {
                        $wxcouponDb = M('cashier_wxcoupon');
                        $wherearr = array('card_id' => $xmlData['CardId'],'mid'=>0);
//                        isset($xmlData['OuterId']) && !empty($xmlData['OuterId']) && $wherearr['mid'] = $xmlData['OuterId'];
                        $wx_coupon = $wxcouponDb->where($wherearr)->find();
                        $cardtype = 0;
                        $outerid = $xmlData['OuterId'];
                        $cardtitle = '';
                        if (!empty($wx_coupon)) {
                            $cardtype = $wx_coupon['card_type'];
                            $outerid = $wx_coupon['mid'];
                            $cardtitle = $wx_coupon['card_title'];
                            $receivenum = $wx_coupon['receivenum'] + 1;
                            $wxcouponDb->where( array('id' => $wx_coupon['id']))->save(array('receivenum' => $receivenum));
                        }
                        $insertData = array(
                            'send_type'=>$wx_coupon['send_type'],
                            'cid'=>$wx_coupon['id'],
                            'openid' => $xmlData['FromUserName'],
                            'give_openId' => isset($xmlData['FromUserName']) ? $xmlData['FromUserName'] : '',
                            'cardid' => $xmlData['CardId'], 'cardtype' => $cardtype, 'cardtitle' => $cardtitle, 'isgivebyfriend' => $xmlData['IsGiveByFriend'], 'cardcode' => $xmlData['UserCardCode'], 'oldcardcode' => $xmlData['OldUserCardCode'], 'outerid' => $outerid, 'addtime' => time());
                        M('cashier_wxcoupon_receive')->add($insertData);
                        
						$fansDb = M('cashier_fans');/***加入粉丝表***/
						$tmpfans = $fansDb->where(array('mid'=>$wx_coupon['mid'],'openid'=>$xmlData['FromUserName']))->find();
						if(empty($tmpfans)){
							$wx_user = M('wx_user')->find();
							

							$wxCardPack = new wxCardPack($wx_user, 0);
							$access_token = $wxCardPack->getToken();
							$wxuserinfo = $wxCardPack->GetwxUserInfoByOpenid($access_token, $xmlData['FromUserName']);
							$fansData = array('mid' => $wx_coupon['mid'], 'openid' => $xmlData['FromUserName']);
							if (isset($wxuserinfo['nickname'])) {
								$fansData['nickname'] = $wxuserinfo['nickname'];
								$fansData['sex'] = $wxuserinfo['sex'];
								$fansData['province'] = $wxuserinfo['province'];
								$fansData['city'] = $wxuserinfo['city'];
								$fansData['country'] = $wxuserinfo['country'];
								$fansData['headimgurl'] = $wxuserinfo['headimgurl'];
								$fansData['groupid'] = $wxuserinfo['groupid'];
							}
							$fansDb->add($fansData);
						}
                    } elseif ($eventV == 'user_del_card') {
                        /** 用户删除* */
                        M('cashier_wxcoupon_receive')->where(array('cardid' => $xmlData['CardId'], 'openid' => $xmlData['FromUserName'], 'cardcode' => $xmlData['UserCardCode']))->save(array('isdel' => 1, 'deltime' => time()) );
                    } elseif ($eventV == 'user_consume_card') {
                        /*                         * 核销* */
                        $wxcouponDb = M('cashier_wxcoupon');
                        $wherearr = array('card_id' => $xmlData['CardId'],'mid'=>$this->mid);
                        $wx_coupon = $wxcouponDb->where($wherearr)->find();
                        if (!empty($wx_coupon)) {
                            $consumenum = $wx_coupon['consumenum'] + 1;
                            $wxcouponDb->where( array('id' => $wx_coupon['id']))->save(array('consumenum' => $consumenum));
                        }
                        M('cashier_wxcoupon_receive')->where(array('cardid' => $xmlData['CardId'], 'openid' => $xmlData['FromUserName'], 'cardcode' => $xmlData['UserCardCode']))->save(array('status' => 1, 'consumetime' => time(), 'consumesource' => isset($xmlData['ConsumeSource']) ? $xmlData['ConsumeSource'] : ''));
                    } elseif ($eventV == 'need_push_on_view') {
                        /*                         * * 进入会员卡事件推送**暂时没用** */
                    } elseif ($eventV == 'user_enter_session_from_card') {
                        /*                         * * 从卡券进入公众号会话事件推送**暂时没用** */
                    }elseif($eventV == 'subscribe'){
                        $result=$this->send_card($xmlData['FromUserName'],1);
//                        $time=time();
//
//                        $wherearr = "send_type=1 and end_timestamp > $time";
//                        $wx_coupon = M('cashier_wxcoupon')->where($wherearr)->find();
//
//                       $openid= ;
//                        $count=M('cashier_wxcoupon_receive')->where(array('send_type'=>1,'openid'=>$openid))->count();
//
//                        if(!empty($wx_coupon)){
//                            if($count==0 && $wx_coupon['quantity']> $wx_coupon['receivenum']){
//                            }
//                        }
                    }elseif($eventV=='CLICK'){

                            $keyword = trim($xmlData['EventKey']);

                        if(empty($keyword))
                            exit("Input something...");

                        // 图文回复
                        $wx_img = M('wx_img')->where("keyword like '%$keyword%'")->find();
                        if($wx_img)
                        {
                            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <ArticleCount><![CDATA[%s]]></ArticleCount>
                                <Articles>
                                    <item>
                                        <Title><![CDATA[%s]]></Title>
                                        <Description><![CDATA[%s]]></Description>
                                        <PicUrl><![CDATA[%s]]></PicUrl>
                                        <Url><![CDATA[%s]]></Url>
                                    </item>
                                </Articles>
                                </xml>";
                            $resultStr = sprintf($textTpl,$xmlData['FromUserName'],$xmlData['ToUserName'],time(),'news','1',$wx_img['title'],$wx_img['desc']
                                , $wx_img['pic'], $wx_img['url']);
                            exit($resultStr);
                        }


                        // 文本回复
                        $wx_text = M('wx_text')->where("keyword like '%$keyword%'")->find();
                        if($wx_text)
                        {
                            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
                            $contentStr = $wx_text['text'];
                            $resultStr = sprintf($textTpl, $xmlData['FromUserName'],$xmlData['ToUserName'], time(), 'text', $contentStr);
                            exit($resultStr);
                        }


                        // 其他文本回复
                        $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
                        $contentStr = "欢迎来到{$this->tpshop_config['shop_info_store_title']}商城!";
                        $resultStr = sprintf($textTpl, $xmlData['FromUserName'],$xmlData['ToUserName'], time(), 'text', $contentStr);
                        exit($resultStr);
                    }

                } elseif ($MsgType == 'text') {
//                    $result=$this->sendcard($openid,$wx_coupon['card_id']);
                    $this->sendcard($xmlData['FromUserName'],1);

                } elseif ($MsgType == 'user_pay_from_pay_cell')  {
                    
                }



            } else {
                $this->replywx();
            }
            $this->replywx();
        } else {
            $this->replywx();
        }
    }

    //回复文本消息
    private function transmitText($obj,$content){
        //文本回复的格式
        $textTpl = "<xml>
					 <ToUserName><![CDATA[%s]]></ToUserName>
					 <FromUserName><![CDATA[%s]]></FromUserName>
					 <CreateTime>%s</CreateTime>
					 <MsgType><![CDATA[text]]></MsgType>
					 <Content><![CDATA[%s]]></Content>
					 <FuncFlag>0</FuncFlag>
					</xml>";
        $fromUsername = $obj['FromUserName'];
        $toUsername = $obj['ToUserName'];
        $time = time();
        $str = sprintf($textTpl, $fromUsername, $toUsername, $time, $content);
        return $str;
    }
    public function replywx($data = '') {
        if (empty($data)) {
            echo "";
            exit();
        } else {
            /* $time = time();
			  $textTpl = "<xml>
			  <ToUserName><![CDATA[%s]]></ToUserName>
			  <FromUserName><![CDATA[%s]]></FromUserName>
			  <CreateTime>%s</CreateTime>
			  <MsgType><![CDATA[%s]]></MsgType>
			  <Content><![CDATA[%s]]></Content>
			  <FuncFlag>0</FuncFlag>
			  </xml>";
			  if ($keyword == "?" || $keyword == "？") {
			  $msgType = "text";
			  $contentStr = date("Y-m-d H:i:s", time());
			  $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
			  echo $resultStr;
             } */
        }
    }

}

?>