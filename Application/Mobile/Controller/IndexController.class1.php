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
 * $Author: 当燃 2016-01-09
 */
namespace Mobile\Controller;
use Mobile\Logic\GoodsLogic;
class IndexController extends MobileBaseController {
    public $user_id;
    public function _initialize() {
        parent::_initialize();
        if(session('?User'))
        {
        $user = session('User');
            $user = M('users')->where("user_id = {$user['user_id']}")->find();
        session('User',$user);  //覆盖session 中的 User
        $this->user = $user;
        $this->user_id = $user['user_id'];
            $this->assign('User',$user); //存储用户信息

        }
    }

    public function research(){
        if(IS_AJAX){
            $ids=I('ids');
            $sort=I('sort');
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
            $this->ajaxReturn(array('status'=>1,'msg'=>"投票成功"));
        }
        $research_list=M('research_list')->where('is_star =1')->limit(1)->find();
        $this->assign('research_list',$research_list);
        $question=M('research')->where(array('pid'=>$research_list['id'],'enabled'=>1))->order('orderby desc')->select();
        foreach($question as $k=>$v){
            $question[$k]['option']=unserialize($v['option']);
        }

        $this->assign('question',$question);
        $this->display();
    }
    public function send_card($openid='oeXkJuK_XwAtEJus78bWF_jMAuVM',$card_id='peXkJuDN78Y6Csf-7oyAoewDeHtQ'){
        $cardTicket=$this->getcardApiTicket();
        $code ='';
        $timestamp =time();
//        $card_id ="peXkJuDN78Y6Csf-7oyAoewDeHtQ";
        $api_ticket =$cardTicket;
        $nonce_str =create_noncestr();
        $temp= array($code, $timestamp, $card_id,$api_ticket,$nonce_str);
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
            return true;
        }

        return false;

}
    public function index(){
        $GoodsLogic = new GoodsLogic();
        $cat_lists = $GoodsLogic->goods_cat_list();
        $this->assign('cat_lists',$cat_lists);

        if(IS_POST){
            if(!$this->user_id){
                header("location:".U('Mobile/User/login'));
                exit;
            }
            $data=array(
                'username'=>I('post.username',''),
                'mobile'=> I('post.mobile',''),
                'user_id'=> $this->user_id,
                'type_id'=> I('post.type_id',''),
                'store_id'=> I('post.store_id',''),
                'yuyue_time'=> strtotime(I('post.yuyue_time','')),
                'addtime'=>time()
            );

            $res=M('apointment')->add($data);
            if($res){
                $this->success('提交成功',U('Mobile/index/index'));
                exit;
            }

        }
        $apointtypelist=M('apointtype')->select();
        $this->assign('apointtypelist',$apointtypelist);

        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;
            // 微信Jssdk 操作类 用分享朋友圈 JS
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();
            print_r($signPackage);
        */
       $now_time = time();
        $hot_goods = M('goods')->where("is_integral=0 and is_hot=1 and is_on_sale=1 and del=0 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time)")->order('goods_id DESC')->limit(8)->select();//首页热卖商品
//        show_bug($hot_goods);
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->select();
        $this->assign('thems',$thems);
        $this->assign('hot_goods',$hot_goods);
        $favourite_goods = M('goods')->where("is_integral=0 and is_recommend=1 and is_on_sale=1 and del=0 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time)")->order('goods_id DESC')->limit(4)->select();//首页推荐商品
        $new_goods = M('goods')->where("is_integral=0 and is_recommend=1 and is_on_sale=1 and del=0 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time)")->order('goods_id DESC')->limit(3)->select();//首页上新商品
        $this->assign('favourite_goods',$favourite_goods);
        $this->assign('new_goods',$new_goods);
        //门店列表

        $store_list = M('store')->where(array('status'=>1))->select();
        $this->assign('store_list',$store_list);

        //广告
        $ad1=M('ad')->where(array('pid'=>257))->order('orderby desc')->find();
        $ad2=M('ad')->where(array('pid'=>258))->order('orderby desc')->find();
        $ad3=M('ad')->where(array('pid'=>256))->order('orderby desc')->find();
        $this->assign('ad1',$ad1);
        $this->assign('ad2',$ad2);
        $this->assign('ad3',$ad3);

        //红包
        $time=time();
        $condition="send_start_time < $time and send_end_time >$time";
        $redbag=M('redbag')->where($condition)->order('id desc')->find();
        if($redbag){
            $red_count=M('redbag_list')->where(array('cid'=>$redbag,'uid'=>$this->user_id))->find();
        }
//        $this->assign('redbag',$red_count);
        $this->assign('redbag',$redbag);
        $today=date('Y-m-d H:i');
        $this->assign('today',$today);
       //查询商品分类
//        $GoodsLogic = new GoodsLogic();
        $cat_list = M('goods_category')->where(array('parent_id'=>0,'is_show'=>1))->select();
//         show_bug($cat_list);
        $this->assign('cat_list',$cat_list);




        $this->display();
    }
    public function like_redbag(){

        if(!$this->user_id){
            $this->ajaxReturn(array('status'=>-1,'msg'=>'请先登录'));
        }
        $user = M('users')->where( array('user_id'=>$this->user_id))->find();
        $cid=I('id');
        $command=I('command');
        $redbag=M('redbag')->where(array('id'=>$cid))->find();
        $count=M('redbag_list')->where(array('cid'=>$cid,'uid'=>$this->user_id))->count();

        if(!$redbag){
            $this->ajaxReturn(array('status'=>-1,'msg'=>'红包不存在'));
        }
        if(($redbag['createnum']- $redbag['send_num'])<=0 && $redbag['createnum'] !=0){
            $this->ajaxReturn(array('status'=>-1,'msg'=>'红包已发放完毕'));
        }
        if($command != $redbag['command']){
            $this->ajaxReturn(array('status'=>-1,'msg'=>'口令错误'));
        }
        if($count>0){
            $this->ajaxReturn(array('status'=>-1,'msg'=>'你已经领取过了'));
        }
        if($redbag['usetype']==1){
            $data['cid']=$cid;
            $data['usetype']=$redbag['usetype'];
            $data['uid']=$this->user_id;
            $data['send_time']=time();
            $data1['send_num']=$redbag['coupon']+1;
            $data2['user_money']=$user['user_money']+$redbag['money'];

            M('redbag_list')->add($data);
            M('redbag')->where(array('id'=>$cid))->save($data1);
            M('users')->where(array('user_id'=>$this->user_id))->save($data2);
            M('account_log')->add(array('user_id'=>$this->user_id,'user_money'=>$redbag['money'],'change_time'=>time(),'desc'=>'红包领取'));
            $this->ajaxReturn(array('status'=>1,'msg'=>'红包领取成功'));

        }else{
            if(!$user['openid']){
                $this->ajaxReturn(array('status'=>-1,'msg'=>'不是微信登录无法领取红包'));
            }
            $result = $this->sendmoney($redbag['money'],$user['openid']);
            if(!$result){
                $this->ajaxReturn(array('status'=>-1,'msg'=>'红包系统故障'));
            }
//            $result = $this->sendmoney(1,'oeXkJuK_XwAtEJus78bWF_jMAuVM');
//            logResult1(var_export($result,true));
            $data=array('cid'=>$cid,'usetype'=>$redbag['usetype'],'uid'=>$this->user_id,'send_time'=>time());
            $data1['send_num']=$redbag['coupon']+1;

            M('redbag_list')->add($data);
            M('redbag')->where(array('id'=>$cid))->save($data1);
            $this->ajaxReturn(array('status'=>1,'msg'=>'红包领取成功'));
        }

    }
    // 红包发送
    public function sendmoney($money,$openid){
//        $log=M('cjlog');
        $money*=100;
        $result=$this->pay($money,$openid);
        if($result->return_code=='SUCCESS'){
            if($result->result_code=='SUCCESS'){
//                $data1['money']=$money/100;
//                $data1['mch_billno']=$result->mch_billno;
//                $log->add($data1);
                return true;
            }
        }
        return false;
    }
    //红包
    function pay($money,$reopenid){
        $paymentPlugin = M('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']);
        $obj['wxappid']=$config_value['appid'];//C('APPID');
//        $obj['wxappid']='wxeedf0b587d29acd2';//C('APPID');
        $obj['mch_id']=$config_value['mchid'];//$config['mch_id'];
//        $obj['mch_id']='1239495002';//$config['mch_id'];
        $obj['mch_billno']=$config_value['mchid'].date('YmdHis').rand(1000,9999);
        $obj['client_ip']=$_SERVER['REMOTE_ADDR'];
        $obj['re_openid']=$reopenid;
        $obj['total_amount']=$money;
        $obj['total_num']=1;
        $obj['send_name']='美之钻';
        $obj['wishing']='恭喜您领取口令红包';
        $obj['act_name']="口令红包";
        $obj['remark']="红包发送";
        $obj['nonce_str'] = create_noncestr();
        $obj['sign'] = getSign($obj,$config_value['key']);
//        $obj['sign'] = getSign($obj,'123456789qwertyuiopasdfghjklzxcv');
//        logResult1(var_export($obj,true));
        $postXml =arrayToXml($obj);

        $url='https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $responseXml = curl_post_ssl($url,$postXml);

        $xml = json_decode(json_encode(simplexml_load_string($responseXml,'SimpleXMLElement', LIBXML_NOCDATA),true));
//        logResult1(var_export($xml,true));
        return $xml;


    }
    //改样式后删除
    public function index1(){

        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;
            // 微信Jssdk 操作类 用分享朋友圈 JS
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();
            print_r($signPackage);
        */
        $hot_goods = M('goods')->where("is_hot=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->select();//首页热卖商品
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->select();
        $this->assign('thems',$thems);
        $this->assign('hot_goods',$hot_goods);
        $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        $this->display();
    }

    /**
     * 分类列表显示
     */
    public function categoryList(){
        $this->display();
    }

    /**
     * 模板列表
     */
    public function mobanlist(){
        $arr = glob("D:/wamp/www/svn_tpshop/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_tpshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";
        }
    }

    /**
     * 商品列表页
     */
    public function goodsList(){
        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
        $id = I('get.id',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);

        $this->display();
    }
    public function ajaxGetMore(){
        $p = I('p',1);
        $favourite_goods = M('goods')->where("is_integral=0 and is_on_sale=1")->order('goods_id DESC')->page($p,10)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        $this->display();
    }
    /*
   * 门店列表
   */
//    public function store_list(){
//        $store_list = M('store')->where(array('status'=>1))->select();
//
//        $this->assign('store_list',$store_list);
//        $this->display();
//    }

    public function getstore(){
        $lng=$_POST['lng'];
        $lat=$_POST['lat'];
        $storelist=$this->get_shop_list($lng,$lat);
        $store=$storelist[0];
        $this->ajaxReturn(array('status'=>1,'address'=>$store['address'],'store_id'=>$store['id']));
    }
    private function get_shop_list($user_lng,$user_lat){
        $user = M('store');
        //查询正常营业的门店
        $condition1['status'] = 1;
        $temp_result_ok = $user->where($condition1)->select();

        //查询尚未营业的门店
        $condition2['status'] = 0;
        $temp_result_error = $user->where($condition2)->select();

        $temp_result = array();
        if (   ($user_lat != '') && ($user_lng != '') ){
            //计算开始营业门店地址与客户的距离
            foreach ( $temp_result_ok as $n=>$v ){
                if ( $temp_result_ok[$n]['zuobiao'] ){
                    $temp_arr = explode(",", $temp_result_ok[$n]['zuobiao']);
                    $temp_lng = $temp_arr[0];
                    $temp_lat = $temp_arr[1];
                    $temp_result_ok[$n]['distance'] = $this->get_distance($temp_lat, $temp_lng,$user_lng,$user_lat);
                }else{
                    $temp_result_ok[$n]['distance'] = 999999999999999;
                }
            }

            //对距离进行冒泡排序(从近到远)
            $temp_result_ok= $this->quickSort($temp_result_ok);

            //计算尚未营业门店地址与客户的距离
            foreach ( $temp_result_error as $n=>$v ){
                if ( $temp_result_error[$n]['zuobiao'] ){
                    $temp_arr = explode(",", $temp_result_error[$n]['zuobiao']);
                    $temp_lng = $temp_arr[0];
                    $temp_lat = $temp_arr[1];
                    $temp_result_error[$n]['distance'] = $this->get_distance($temp_lat, $temp_lng,$user_lng,$user_lat);
                }else{
                    $temp_result_error[$n]['distance'] = 999999999999999;
                }
            }

            //对距离进行冒泡排序(从近到远)
            $temp_result_error= $this->quickSort($temp_result_error);

            foreach ( $temp_result_ok as $n=>$v ){
                $temp_result[] = $temp_result_ok[$n];
            }

            foreach ( $temp_result_error as $n=>$v ){
                $temp_result[] = $temp_result_error[$n];
            }

        }else{
            foreach ( $temp_result_ok as $n=>$v ){
                $temp_result[] = $temp_result_ok[$n];
            }

            foreach ( $temp_result_error as $n=>$v ){
                $temp_result[] = $temp_result_error[$n];
            }
        }

        return $temp_result;
    }
    //根据经纬度计算距离的函数
    private function get_distance($lat2,$lng2,$user_lng,$user_lat){
        $earthRadius = 6367000;
        //用户经纬度
        $lat1 = ($user_lat * pi()) / 180;
        $lng1 = ($user_lng * pi()) / 180;
        //门店经纬度
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }
    // 快速排序的算法
    public function quickSort($arr)
    {
        $array = $arr;
        $flag = false;
        for ($i = 1; $i < count($array); $i ++) {
            for ($j = 0; $j < count($array) - $i; $j ++) {
                if ($array[$j]['distance'] > $array[$j + 1]['distance']) {
                    $temp = $array[$j];
                    $array[$j] = $array[$j + 1];
                    $array[$j + 1] = $temp;
                    $flag = true;
                }
            }
            if (! $flag) {
                break;
            }
            $flag = false;
        }

        return $array;
    }
    public function classindex(){

        $GoodsLogic = new GoodsLogic();
        $cat_lists = $GoodsLogic->goods_cat_list();
        if(I('firstid')){
            $firstid=I('firstid');
        }else{
            $firstid=$cat_lists[0]['id'];
        }
        $this->assign('firstid',$firstid);
        $this->assign('cat_lists',$cat_lists);
//        show_bug($cat_lists);
        $this->display();
    }

    public function greet_card(){
        layout(false);
//        show_bug($this->user_id);

        $this->display();
    }

}