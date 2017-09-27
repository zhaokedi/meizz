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
use Home\Logic\CartLogic;
use Think\AjaxPage;
use Think\Page;

class GoodsController extends MobileBaseController {

	public $user_id = 0;
	public $user = array();
    public function index(){
       // $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover,{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
        $this->display();
    }

	/**
	 * 初始化函数
	 */
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
			// 给用户计算会员价 登录前后不一样

		}
	}
    /**
     * 分类列表显示
     */
    public function categoryList(){
        $this->display();
    }

    /**
     * 商品列表页
     */
    public function goodsList(){
    	$filter_param = array(); // 帅选数组
    	$id = I('get.id',1); // 当前分类id
    	$brand_id = I('brand_id',0);
    	$spec = I('spec',0); // 规格
    	$attr = I('attr',''); // 属性
    	$sort = I('sort','goods_id'); // 排序
    	$sort_asc = I('sort_asc','asc'); // 排序
    	$price = I('price',''); // 价钱
    	$start_price = trim(I('start_price','0')); // 输入框价钱
    	$end_price = trim(I('end_price','0')); // 输入框价钱
    	if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
    	$filter_param['id'] = $id; //加入帅选条件中
    	$brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
    	$spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
    	$attr  && ($filter_param['attr'] = $attr); //加入帅选条件中
    	$price  && ($filter_param['price'] = $price); //加入帅选条件中

    	$goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
    	// 分类菜单显示
    	$goodsCate = M('GoodsCategory')->where("id = $id")->find();// 当前分类
    	//($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
    	$cateArr = $goodsLogic->get_goods_cate($goodsCate);

    	// 帅选 品牌 规格 属性 价格
    	$cat_id_arr = getCatGrandson ($id);
        $now_time =  time();
    	$filter_goods_id = M('goods')->where("is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);

    	// 过滤帅选的结果集里面找商品
    	if($brand_id || $price)// 品牌或者价格
    	{
    		$goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
    		$filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
    	}
    	if($spec)// 规格
    	{
    		$goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
    		$filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个帅选条件的结果 的交集
    	}
    	if($attr)// 属性
    	{
    		$goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
    		$filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个帅选条件的结果 的交集
    	}

    	$filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的帅选菜单
    	$filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 帅选的价格期间
    	$filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选品牌
    	$filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选规格
    	$filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选属性

    	$count = count($filter_goods_id);
    	$page = new Page($count,10);
    	if($count > 0)
    	{
    		$goods_list = M('goods')->where("is_integral=0 and goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
    		$filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
    		if($filter_goods_id2)
    			$goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
    	}
    	$goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
    	$this->assign('goods_list',$goods_list);
    	$this->assign('goods_category',$goods_category);
    	$this->assign('goods_images',$goods_images);  // 相册图片
    	$this->assign('filter_menu',$filter_menu);  // 帅选菜单
    	$this->assign('filter_spec',$filter_spec);  // 帅选规格
    	$this->assign('filter_attr',$filter_attr);  // 帅选属性
    	$this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
    	$this->assign('filter_price',$filter_price);// 帅选的价格期间
    	$this->assign('goodsCate',$goodsCate);
    	$this->assign('cateArr',$cateArr);
    	$this->assign('filter_param',$filter_param); // 帅选条件
    	$this->assign('cat_id',$id);
    	$this->assign('page',$page);// 赋值分页输出
    	$this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
    	C('TOKEN_ON',false);

        if($_GET['is_ajax'])
            $this->display('ajaxGoodsList');
        else
            $this->display();
    }
	public function goodsList1(){

		$filter_param = array(); // 帅选数组
		$id = I('get.id',1); // 当前分类id
		$brand_id = I('brand_id',0);
		$spec = I('spec',0); // 规格
		$attr = I('attr',''); // 属性
		$sort = I('sort','goods_id'); // 排序
		$sort_asc = I('sort_asc','asc'); // 排序
		$price = I('price',''); // 价钱
		$start_price = trim(I('start_price','0')); // 输入框价钱
		$end_price = trim(I('end_price','0')); // 输入框价钱
		if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
		$filter_param['id'] = $id; //加入帅选条件中
		$brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
		$spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
		$attr  && ($filter_param['attr'] = $attr); //加入帅选条件中
		$price  && ($filter_param['price'] = $price); //加入帅选条件中

		$goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
		// 分类菜单显示
		$goodsCate = M('GoodsCategory')->where("id = $id")->find();// 当前分类
		//($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
		$cateArr = $goodsLogic->get_goods_cate($goodsCate);

		// 帅选 品牌 规格 属性 价格
		$cat_id_arr = getCatGrandson ($id);

		$filter_goods_id = M('goods')->where("is_on_sale=1 and cat_id in(".  implode(',', $cat_id_arr).") ")->cache(true)->getField("goods_id",true);

		// 过滤帅选的结果集里面找商品
		if($brand_id || $price)// 品牌或者价格
		{
			$goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
			$filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
		}
		if($spec)// 规格
		{
			$goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
			$filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个帅选条件的结果 的交集
		}
		if($attr)// 属性
		{
			$goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
			$filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个帅选条件的结果 的交集
		}

		$filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的帅选菜单
		$filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 帅选的价格期间
		$filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选品牌
		$filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选规格
		$filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选属性

		$count = count($filter_goods_id);
		$page = new Page($count,4);
		if($count > 0)
		{
			$goods_list = M('goods')->where(" is_integral=0 and goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
			$filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
			if($filter_goods_id2)
				$goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
		}
		$goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
		$this->assign('goods_list',$goods_list);
		$this->assign('goods_category',$goods_category);
		$this->assign('goods_images',$goods_images);  // 相册图片
		$this->assign('filter_menu',$filter_menu);  // 帅选菜单
		$this->assign('filter_spec',$filter_spec);  // 帅选规格
		$this->assign('filter_attr',$filter_attr);  // 帅选属性
		$this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
		$this->assign('filter_price',$filter_price);// 帅选的价格期间
		$this->assign('goodsCate',$goodsCate);
		$this->assign('cateArr',$cateArr);
		$this->assign('filter_param',$filter_param); // 帅选条件
		$this->assign('cat_id',$id);
		$this->assign('page',$page);// 赋值分页输出
		$this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
		C('TOKEN_ON',false);

		if($_GET['is_ajax'])
			$this->display('ajaxGoodsList');
		else
			$this->display();
	}
    /**
     * 商品列表页 ajax 翻页请求 搜索
     */
    public function ajaxGoodsList() {
        $where ='';

        $cat_id  = I("id",0); // 所选择的商品分类id
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " WHERE cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }

        $Model  = new \Think\Model();
        $result = $Model->query("select count(1) as count from __PREFIX__goods $where ");
        $count = $result[0]['count'];
        $page = new AjaxPage($count,10);

        $order = " order by goods_id desc"; // 排序
        $limit = " limit ".$page->firstRow.','.$page->listRows;
        $list = $Model->query("select *  from __PREFIX__goods $where $order $limit");

        $this->assign('lists',$list);
        $html = $this->fetch('ajaxGoodsList'); //$this->display('ajax_goods_list');
       exit($html);
    }

    /**
     * 商品详情页
     */
	public function goodsInfo(){
		C('TOKEN_ON',true);
		$goodsLogic = new \Home\Logic\GoodsLogic();
		$goods_id = I("get.id");
		$goods = M('Goods')->where("goods_id = $goods_id and del = 0")->find();
		if(empty($goods)){
			$this->error('该商品不存在',U('Index/index'));
		}
        if(empty($goods) || ($goods['is_on_sale'] == 0) || $goods['on_time']>time() || ($goods['down_time']<time() && $goods['down_time']!=0)  || tpCache('shop_info.product_show')){
            $this->error('该商品已经下架',U('Index/index'));
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
		$commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
		$this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
		$goods['sale_num'] = M('order_goods')->where("goods_id=$goods_id and is_send=1")->count();

		$where = " session_id = '$this->session_id' ";
		$user_id = $this->user_id ? $this->user_id : 0;
		if($user_id)
			$where .= "  or user_id= $user_id ";
		// 查找购物车商品总数量
		$cart_result =cookie('cart_anum');
		$cart_result=M('cart')->where("user_id=$this->user_id")->sum('goods_num');
        $cart_result = empty($cart_result)?0:$cart_result;
		$this->assign('cart_total_price', $cart_result);
		//商品促销
		if($goods['prom_type'] == 3)
		{
			$prom_goods = M('prom_goods')->where("id = {$goods['prom_id']}")->find();
			$this->assign('prom_goods',$prom_goods);// 商品促销
		}
		$onecomment = M('comment')->where(array('goods_id'=> $goods_id))->order("add_time desc")->limit(1)->find();
		$usercomment = M('users')->where(array('user_id'=> $onecomment['user_id']))->find();

		$now_time =  time();

		if(!empty($goods['linked'])) {
			//关联商品
			$goods_linded = M('goods')->where("linked REGEXP '{$goods['linked']}' and goods_id != {$goods['goods_id']} and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) ")->order("goods_id desc")->select();

			//关联文章
			$article_linded = M('article')->where("linked REGEXP '{$goods['linked']}' and is_open = 1 and publish_time<{$now_time}")->order("add_time desc")->limit(4)->select();
		}
		//超值礼包
		$gift_pack = M('gift_pack')->where("goods_id like '%{$goods['goods_id']}%'")->select();
		foreach($gift_pack as  $k=>$v){
			$gift_pack[$k]['goods_id']=explode("|",$v['goods_id']);
			$gift_pack[$k]['goods_name']=explode("|",$v['goods_name']);
		}
//		show_bug($gift_pack);
		$goods['gift_link']=unserialize($goods['gift_link']);
		$this->assign('gift_pack',$gift_pack);
		$this->assign('goods_linked',$goods_linded);
		$this->assign('article_linked',$article_linded);

		$this->assign('onecomment',$onecomment);//首页一条评论
		$this->assign('usercomment',$usercomment);//首页一条评论用户信息
		$this->assign('commentStatistics',$commentStatistics);//评论概览
//		show_bug($goods_attr_list);

		$this->assign('goods_attribute',$goods_attribute);//属性值
		$this->assign('goods_attr_list',$goods_attr_list);//属性列表
//        $this->assign('catr_count',$catr_count);//属性列表
		$this->assign('filter_spec',$filter_spec);//规格参数
		$this->assign('goods_images_list',$goods_images_list);//商品缩略图
		$goods['discount'] = round($goods['shop_price']/$goods['market_price'],2)*10;
		$this->assign('goods',$goods);
		$this->display();
	}
	public function goodsInfo1(){
		C('TOKEN_ON',true);
		$goodsLogic = new \Home\Logic\GoodsLogic();
		$goods_id = I("get.id");
		$goods = M('Goods')->where("goods_id = $goods_id")->find();
		if(empty($goods)){
			$this->error('此商品不存在或者已下架',U('Index/index'));
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
		$commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
		$this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
		$goods['sale_num'] = M('order_goods')->where("goods_id=$goods_id and is_send=1")->count();

		$where = " session_id = '$this->session_id' ";
		$user_id = $this->user_id ? $this->user_id : 0;
		if($user_id)
			$where .= "  or user_id= $user_id ";
		$catr_count = M('Cart')->where($where)->count(); // 查找购物车商品总数量
		//商品促销
		if($goods['prom_type'] == 3)
		{
			$prom_goods = M('prom_goods')->where("id = {$goods['prom_id']}")->find();
			$this->assign('prom_goods',$prom_goods);// 商品促销
		}

		$onecomment = M('comment')->where(array('goods_id'=> $goods_id))->order("add_time desc")->limit(1)->find();
//		show_bug($onecomment);
		$usercomment = M('users')->where(array('users_id'=> $onecomment['user_id']))->find();

		$this->assign('onecomment',$onecomment);//首页一条评论
		$this->assign('usercomment',$usercomment);//首页一条评论用户信息
		$this->assign('commentStatistics',$commentStatistics);//评论概览


		$this->assign('goods_attribute',$goods_attribute);//属性值
		$this->assign('goods_attr_list',$goods_attr_list);//属性列表
		$this->assign('catr_count',$catr_count);//属性列表
		$this->assign('filter_spec',$filter_spec);//规格参数
		$this->assign('goods_images_list',$goods_images_list);//商品缩略图
		$goods['discount'] = round($goods['shop_price']/$goods['market_price'],2)*10;
		$this->assign('goods',$goods);
		$this->display();
	}
    /**
     * 商品详情页
     */
    public function detail(){
        //  form表单提交
        C('TOKEN_ON',true);
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $goods_id = I("get.id");
        $goods = M('Goods')->where("goods_id = $goods_id")->find();
        $this->assign('goods',$goods);
        $this->display();
    }

    /*
     * 商品评论
     */
    public function comment(){
        $goods_id = I("goods_id",'0');
        $this->assign('goods_id',$goods_id);
        $this->display();
    }

    /*
     * ajax获取商品评论
     */
    public function ajaxComment(){
        $goods_id = I("goods_id",'0');
        $commentType = I('commentType','1'); // 1 全部 2好评 3 中评 4差评
        if($commentType==5){
        	$where = "goods_id = $goods_id and parent_id = 0 and img !='' ";
        }else{
        	$typeArr = array('1'=>'0,1,2,3,4,5','2'=>'4,5','3'=>'3','4'=>'0,1,2');
        	$where = "goods_id = $goods_id and parent_id = 0 and ceil((deliver_rank + goods_rank + service_rank) / 3) in($typeArr[$commentType])";
        }
        $count = M('Comment')->where($where)->count();

        $page = new AjaxPage($count,5);
        $show = $page->show();
        $list = M('Comment')->where($where)->order("add_time desc")->limit($page->firstRow.','.$page->listRows)->select();
        $replyList = M('Comment')->where("goods_id = $goods_id and parent_id > 0")->order("add_time desc")->select();

        foreach($list as $k => $v){
            $list[$k]['img'] = unserialize($v['img']); // 晒单图片
        }
        $this->assign('commentlist',$list);// 商品评论
        $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /*
     * 获取商品规格
     */
    public function goodsAttr(){
        $goods_id = I("get.goods_id",'0');
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id = $goods_id")->select(); // 查询商品属性表
        $this->assign('goods_attr_list',$goods_attr_list);
        $this->assign('goods_attribute',$goods_attribute);
        $this->display();
    }
     /**
     * 商品搜索列表页
     */
    public function search(){

    	$filter_param = array(); // 帅选数组
    	$id = I('get.id',0); // 当前分类id
    	$brand_id = I('brand_id',0);
    	$sort = I('sort','goods_id'); // 排序
    	$sort_asc = I('sort_asc','asc'); // 排序
    	$price = I('price',''); // 价钱
    	$start_price = trim(I('start_price','0')); // 输入框价钱
    	$end_price = trim(I('end_price','0')); // 输入框价钱
    	if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
    	$filter_param['id'] = $id; //加入帅选条件中
    	$brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
    	$price  && ($filter_param['price'] = $price); //加入帅选条件中
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中
        if(empty($q))
            $this->error ('请输入搜索关键词');
        $now_time = time();
    	$goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
    	$filter_goods_id = M('goods')->where("is_on_sale=1 and goods_name like '%{$q}%' and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0")->cache(true)->getField("goods_id",true);

    	// 过滤帅选的结果集里面找商品
    	if($brand_id || $price)// 品牌或者价格
    	{
    		$goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
    		$filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
    	}

    	$filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的帅选菜单
    	$filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 帅选的价格期间
    	$filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选品牌

    	$count = count($filter_goods_id);
    	$page = new Page($count,10);
    	if($count > 0)
    	{
    		$goods_list = M('goods')->where("is_integral=0 and goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
    		$filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
    		if($filter_goods_id2)
    			$goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
    	}
    	$goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
    	$this->assign('goods_list',$goods_list);
    	$this->assign('goods_category',$goods_category);
    	$this->assign('goods_images',$goods_images);  // 相册图片
    	$this->assign('filter_menu',$filter_menu);  // 帅选菜单
    	$this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
    	$this->assign('filter_price',$filter_price);// 帅选的价格期间
    	$this->assign('goodsCate',$goodsCate);
    	$this->assign('filter_param',$filter_param); // 帅选条件
    	$this->assign('page',$page);// 赋值分页输出
    	$this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
    	C('TOKEN_ON',false);

        if($_GET['is_ajax'])
            $this->display('ajaxGoodsList');
        else
            $this->display();
    }

    /**
     * 商品搜索列表页
     */
    public function ajaxSearch()
    {

    }
}