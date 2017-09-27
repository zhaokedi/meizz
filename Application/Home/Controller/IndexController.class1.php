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
use Think\Page;
use Think\Verify;
class IndexController extends BaseController {

	public function index(){
        // 如果是手机跳转到 手机模块
        if(true == isMobile()){
            header("Location: ".U('Mobile/Index/index'));
        }
		$goodsLogic = new \Home\Logic\GoodsLogic();
        //热卖商品
        $goods_category = M('goods_category')->where("is_show=1")->order("sort_order asc")->select();
        $home_hot_goods = M('goods')->where("is_on_sale = 1 and is_hot=1")->order("sort asc")->select();
        foreach ($goods_category as $k => &$cate) {
            foreach ($home_hot_goods as $m => $goods) {
                //只显示前4个商品
                if(count($cate['hot_goods']) == 4){
                    break;
                }
                if($cate['id'] == $goods['cat_id']){
                    $cate['hot_goods'][] = $goods;
                    unset($home_hot_goods[$m]);
                }
            }
            //删除没有热卖商品的分类
            if(empty($cate['hot_goods'])){
                unset($goods_category[$k]);
            }
        }
        foreach ($goods_category as $k => &$cate) {

            if( !in_array($cate['id'],array(15,58,59,29,14))){
                $goods_category1[]=$cate;
            }elseif(in_array($cate['id'],array(15,58,59))){
                $goods_category2[]=$cate;
            }elseif(in_array($cate['id'],array(29,14))){
                $goods_category3[]=$cate;
            }
        }

        if(IS_POST){
            $data=array(
                'username'=>I('post.username',''),
                'mobile'=> I('post.mobile',''),
                'address'=> I('post.address',''),
                'type_id'=> I('post.type_id',''),
                'store_id'=> I('post.store_id',''),
                'yuyue_time'=> strtotime(I('post.yuyue_time','')),
                'addtime'=>time()
            );

            $res=M('apointment')->add($data);
            if($res){
                $this->success('预约提交成功,我们的客服会主动联系您，请耐心等待',U('Home/index/index'));
                exit;
            }

        }
        //文章
        $article_list=M('article')->where(array('cat_id'=>9,'is_open'=>1))->select();
//        foreach($article_list as $k=>$v){
//            $article_list[$k]['content']=htmlspecialchars_decode($v['content']);
//        }
        $this->assign('article_list',$article_list);
        //门店列表
        $store_list = M('store')->where(array('status'=>1))->select();
        $this->assign('store_list',$store_list);
        $apointtypelist=M('apointtype')->select();
        $this->assign('apointtypelist',$apointtypelist);
        // print_r($goods_category);die;
        //首页推荐商品
        $recommend_goods = M('goods')->where("is_recommend = 1")->order("sort")->limit(3)->select();
		$cat_id_arr = getCatGrandson(tpCache('shop_info.cat_id'));
		$now_time =  time();
		$filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true);
		$filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'home',1); // 获取指定分类下的筛选规格
		$specv1=intval(count($filter_spec)/2)+(count($filter_spec)%2);
		$specv2=intval(count($filter_spec)/2);
		$filter_spec1 = array();
		$filter_spec2 = array();
		$i=0;
		foreach($filter_spec as $k => $v)
         {
			 if($i<$specv1)
			 {
				$filter_spec1[$k] = $v;
		     }
			 if($i>=$specv1)
			 {
				$filter_spec2[$k] = $v;
		     }
			 $i++;
         }
        //banner
        $banner = M('ad')->where('enabled = 1 and pid = 2')->select();
        $this->assign('hot',$goods_category);
        $this->assign('hot1',$goods_category1);
        $this->assign('hot2',$goods_category2);
        $this->assign('hot3',$goods_category3);
        $this->assign('banner',$banner);
		$this->assign('specv1',$specv1);
        $this->assign('recommend_goods',$recommend_goods);
		$this->assign('filter_spec1',$filter_spec1);  // 筛选规格
		$this->assign('filter_spec2',$filter_spec2);  // 筛选规格
        $this->display();
    }

	public function diyindex(){
        // 如果是手机跳转到 手机模块
        if(true == isMobile()){
            header("Location: ".U('Mobile/Index/index'));
        }
		$goodsLogic = new \Home\Logic\GoodsLogic();
        //热卖商品
        $goods_category = M('goods_category')->where("is_show=1")->order("sort_order asc")->select();
        $home_hot_goods = M('goods')->where("is_on_sale = 1 and is_hot=1")->order("sort asc")->select();
        foreach ($goods_category as $k => &$cate) {
            foreach ($home_hot_goods as $m => $goods) {
                //只显示前4个商品
                if(count($cate['hot_goods']) == 4){
                    break;
                }
                if($cate['id'] == $goods['cat_id']){
                    $cate['hot_goods'][] = $goods;
                    unset($home_hot_goods[$m]);
                }
            }
            //删除没有热卖商品的分类
            if(empty($cate['hot_goods'])){
                unset($goods_category[$k]);
            }
        }
        // print_r($goods_category);die;
        //首页推荐商品
        $recommend_goods = M('goods')->where("is_recommend = 1")->order("sort")->limit(3)->select();

		$cat_id_arr = getCatGrandson (57);
		$now_time =  time();
		$filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);
		$filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'home',1); // 获取指定分类下的筛选规格
		$specv1=intval(count($filter_spec)/2)+(count($filter_spec)%2);
		$specv2=intval(count($filter_spec)/2);
		$filter_spec1 = array();
		$filter_spec2 = array();
		$i=0;
		foreach($filter_spec as $k => $v)
         {
			 if($i<$specv1)
			 {
				$filter_spec1[$k] = $v;
		     }
			 if($i>=$specv1)
			 {
				$filter_spec2[$k] = $v;
		     }
			 $i++;
         }
        //banner
        $banner = M('ad')->where('enabled = 1 and pid = 2')->select();
        $this->assign('hot',$goods_category);
        $this->assign('banner',$banner);
		$this->assign('specv1',$specv1);
        $this->assign('recommend_goods',$recommend_goods);
		$this->assign('filter_spec1',$filter_spec1);  // 筛选规格
		$this->assign('filter_spec2',$filter_spec2);  // 筛选规格
        $this->display();
    }


    /**
     *  公告详情页
     */
    public function notice(){
        $this->display();
    }

    // 二维码
    public function qr_code(){
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.tp-shop.cn/Home/Index/erweima/data/www.99soubao.com
         require_once 'ThinkPHP/Library/Vendor/phpqrcode/phpqrcode.php';
          //import('Vendor.phpqrcode.phpqrcode');
            error_reporting(E_ERROR);
            $url = urldecode($_GET["data"]);
            \QRcode::png($url);
    }

    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';

        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }

    // 促销活动页面
    public function promoteList()
    {
        $Model = new \Think\Model();
        $goodsList = $Model->query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id   where ".time()." > start_time  and ".time()." < end_time");
        $brandList = M('brand')->getField("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        $this->display();
    }
    //定位
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
}