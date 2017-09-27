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
use Think\AjaxPage;
use Think\Page;
use Think\Verify;
class GoodsController extends BaseController {
    public $user_id = 0;
    public $user = array();
    /*
    * 初始化操作
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

        }
    }

    public function addfenqi(){
        $mobile=I('mobile');
        if($this->user_id == 0)
            $this->ajaxReturn(array('status'=>2,'msg'=>'您未登录,请先登录'));
        $data['mobile']=$mobile;
        $data['username']=I('username');
        $data['remark']=I('remark');
        $data['num']=I('num');
        $data['user_id']=$this->user_id;
        $data['goods_id']=I('goods_id');
        $data['addtime']=time();
        $res=M('fenqi')->add($data);
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>'提交成功'));
        }else{
            $this->ajaxReturn(array('status'=>2,'msg'=>'提交失败'));
        }
    }
    public function index(){
        $this->redirect('goodsList');
        $this->display();
    }
   /**
    * 商品详情页
    */
    public function goodsInfo(){
        //  form表单提交
		$now_time =  time();
        C('TOKEN_ON',true);
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $goods_id = I("get.id");
        $goods = M('Goods')->where("goods_id = $goods_id and del = 0")->find();
        if(empty($goods)){
            $this->error('该商品不存在',U('Index/index'));
        }
        if ($goods['store_count']==0) {
            $state['state'] = 1;
            $result = M('Goods')->where("goods_id = $goods_id")->save($state);
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

        //商品是否正在促销中
        if($goods['prom_type'] == 1)
        {
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);
            $flash_sale = M('flash_sale')->where("id = {$goods['prom_id']}")->find();
            $this->assign('flash_sale',$flash_sale);
        }

        $spec_goods_price  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        //热销榜
        $hot = M('goods')->where("is_hot = 1 and cat_id = {$goods['cat_id']}")->order("goods_id desc")->limit(3)->select();
        //推荐
        $recommend = M('goods')->where("is_recommend = 1 and cat_id = {$goods['cat_id']}")->order("goods_id desc")->limit(4)->select();

		if(!empty($goods['linked']))
		{
		//关联商品
        $linkeds = M('goods')->where("linked REGEXP '{$goods['linked']}' and goods_id != {$goods['goods_id']} and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) ")->order("goods_id desc")->limit(6)->select();

		//关联文章
        $articles =  M('article')->where("linked REGEXP '{$goods['linked']}' and is_open = 1 and publish_time<{$now_time}")->order("add_time desc")->limit(4)->select();
		}

        $goods['gift_link']=unserialize($goods['gift_link']);
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($goods_id,1));// 面包屑导航
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('hot',$hot);//热销榜
        $this->assign('recommend',$recommend);//店长推荐
		$this->assign('linkeds',$linkeds);//关联商品
		$this->assign('articles',$articles);//关联文章
        $this->assign('goods_attribute',$goods_attribute);//属性值
        $this->assign('goods_attr_list',$goods_attr_list);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('siblings_cate',$goodsLogic->get_siblings_cate($goods['cat_id']));//相关分类
        $this->assign('look_see',$goodsLogic->get_look_see($goods));//看了又看
        $this->assign('goods',$goods);
        $this->display();
    }

    /*public function goodsList(){
        $goods = M('goods');
        //尺寸、风格、地区列表
        $sizeList = $goods->where("size IS NOT NULL")->distinct(true)->field('size')->order("size asc")->select();
        $styleList = $goods->where("style IS NOT NULL")->distinct(true)->field('style')->order("style asc")->select();
        $areaList = $goods->where("area IS NOT NULL")->distinct(true)->field('area')->order("area asc")->select();

        $get = I('get.');
        $pageNum = 20;
        $page = empty($get['page'])?0:($get['page']-1)*$pageNum;
        //查询条件
        if(!empty($get['keywords'])){
            $condition['goods_name'] = array('LIKE',"%{$get['keywords']}%");
            $condition['goods_remark'] = array('LIKE',"%{$get['keywords']}%");
            $condition['_logic'] = 'OR';
        }
        if(!empty($get['field'])){
            $filter = base64_decode($get['filter']);
            // echo $filter;
            $condition[$get['field']] = array('LIKE',"%{$filter}%");
        }
        if(!empty($condition)){
            $map['_complex'] = $condition;
        }
        $map['is_on_sale'] = array('eq',1); //是否上架
        //排序
        $sort = 'goods_id';      //按id
        $rule = 'desc';          //升序
        if(!empty($get['sort'])){
            $sort = $get['sort'];
        }
        if(!empty($get['field'])){
            $sort = $get['field'];
        }
        if(!empty($get['rule'])){
            $rule = $get['rule'];
        }
        if($rule == 'asc'){
            $icon = 'up';        //排序的图标
            $next_rule = 'desc'; //下次点击为"降序"
        }else{
            $icon = 'down';
            $next_rule = 'asc';  //下次点击为"升序"
        }
        //总记录数
        $count = $goods->where($map)->count();
        //分页
        $pageObj = new Page($count,$pageNum);
        $pageShow = $pageObj->show();
        $pageObj->parameter['sort'] = $sort;
        $pageObj->parameter['rule'] = $rule;
        // $pageObj->parameter['field'] = $get['field'];
        //最大页
        $maxPage = ceil($count/$pageNum);
        //获取商品
        if(empty($get['cat_id'])){
            $goodsList = $goods->where($map)->order("{$sort} {$rule}")->limit($pageObj->firstRow,$pageObj->listRows)->select();
        }else{
            // echo 'ddd';
        }
        if(IS_AJAX){
            foreach ($goodsList as $item) {
                $html .= '<div class="item"><a href="/Home/Goods/goodsInfo/id/'.$item['goods_id'].'.html">';
                $html .= '<img src="'.goods_thum_images($item['goods_id'],252,300,1).'" /></a>';
                $html .= '<p class="small"><span class="text-muted">¥'.$item['shop_price'].'</span><br />';
                $html .= '<strong>'.getSubstr($item['goods_name'],0,16).'</strong><br />';
                $html .= '<span class="text-muted">'.getSubstr($item['goods_remark'],0,20).'</span></p></div>';
            }
            echo $html;
        }else{
            $this->assign('pageShow',$pageShow);// 赋值分页输出
            $this->assign('maxPage',$maxPage);// 最大页
            $this->assign('goodsList',$goodsList);
            $this->assign('sizeList',$sizeList);
            $this->assign('styleList',$styleList);
            $this->assign('areaList',$areaList);
            $this->assign('parameter',$pageObj->parameter);
            $this->assign('icon',$icon);
            $this->assign('next_rule',$next_rule);
            $this->display();
        }
    }*/
    /**
     * 商品列表页
     */
    public function goodsList(){

        $key = md5($_SERVER['REQUEST_URI'].$_POST['start_price'].'_'.$_POST['end_price']);
        $html = S($key);
        if(!empty($html))
        {
            exit($html);
        }
        $pageNum = 20;
        $filter_param = array(); // 筛选数组
        $id = I('get.id',0); // 当前分类id
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        if($id){
            $filter_param['id'] = $id; //加入筛选条件中
            // $goods_category_tree = M('goods_category')->where("is_show = 1")->order('id asc')->find();
            // $id = $goods_category_tree['id'];
        }

        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类

        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id = $id")->find();// 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
		$now_time =  time();
        $filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);

        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选品牌
        $filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选规格
        $filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选属性

        $count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
        }
        // print_r($filter_menu);
		if(tpCache('shop_info.product_show')){
		    unset($goods_list);
		}
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        $navigate_cat = $id?navigate_goods($id):array('全部商品'); // 面包屑导航
        $this->assign('goods_list',$goods_list);
        $this->assign('navigate_cat',$navigate_cat);
        $this->assign('goods_category',$goods_category);
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_spec',$filter_spec);  // 筛选规格
        $this->assign('filter_attr',$filter_attr);  // 筛选属性
        $this->assign('filter_brand',$filter_brand);  // 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('goodsCate',$goodsCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        C('TOKEN_ON',false);
        $html = $this->fetch();
        S($key,$html);
        echo $html;
    }

    /**
     * 商品搜索列表页
     */
    public function search()
    {
		$now_time =  time();
        $pageNum = 20;
       //C('URL_MODEL',0);
        $filter_param = array(); // 筛选数组
        $id = I('get.id',0); // 当前分类id
        $brand_id = I('brand_id',0);
        $sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price')); // 输入框价钱
        $end_price = trim(I('end_price')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        empty($q) && $this->error('请输入搜索词');

        $id && ($filter_param['id'] = $id); //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入筛选条件中

        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类

        $where = "goods_name like '%{$q}%' and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0";
        if($id)
        {
            $cat_id_arr = getCatGrandson ($id);
            $where .= " and cat_id in(".  implode(',', $cat_id_arr).")";
        }

        $search_goods = M('goods')->where($where)->getField('goods_id,cat_id');
        $filter_goods_id = array_keys($search_goods);
        $filter_cat_id = array_unique($search_goods); // 分类需要去重
        if($filter_cat_id)
        {
            $cateArr = M('goods_category')->where("id in(".implode(',', $filter_cat_id).")")->select();
            $tmp = $filter_param;
            foreach($cateArr as $k => $v)
            {
                $tmp['id'] = $v['id'];
                $cateArr[$k]['href'] = U("/Home/Goods/search",$tmp);
            }
        }
        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'search'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'search'); // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的筛选品牌

        $count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->select();
        }
		if(tpCache('shop_info.product_show')){
		    unset($goods_list);
		}
        $this->assign('goods_list',$goods_list);
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_brand',$filter_brand);  // 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        C('TOKEN_ON',false);
        $this->display("goodsList");
    }

    /**
     * 商品咨询ajax分页
     */
    public function ajaxConsult(){
        $goods_id = I("goods_id",'0');
        $consult_type = I('consult_type','0'); // 0全部咨询  1 商品咨询 2 支付咨询 3 配送 4 售后

        $where = " parent_id = 0 and goods_id = $goods_id";
        if($consult_type > 0)
            $where .= " and consult_type = $consult_type ";

        $count = M('GoodsConsult')->where($where)->count();
        $page = new AjaxPage($count,5);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        $show = $page->show();
        $list = M('GoodsConsult')->where($where)->order("id desc")->limit($page->firstRow.','.$page->listRows)->select();
        $replyList = M('GoodsConsult')->where("parent_id > 0")->order("id desc")->select();

        $this->assign('consultCount',$count);// 商品咨询数量
        $this->assign('consultList',$list);// 商品咨询
        $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 商品评论ajax分页
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

        $page = new AjaxPage($count,6);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        $show = $page->show();
        $list = M('Comment')->where($where)->order("add_time desc")->limit($page->firstRow.','.$page->listRows)->select();
        $replyList = M('Comment')->where("goods_id = $goods_id and parent_id > 0")->order("add_time desc")->select();

        foreach($list as $k => $v){
            $list[$k]['img'] = unserialize($v['img']); // 晒单图片
        }
        $this->assign('commentlist',$list);// 商品评论
        $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('totalPages',$page->totalPages);// 总页数
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     *  商品咨询
     */
    public function goodsConsult(){
        //  form表单提交
        C('TOKEN_ON',true);
        $goods_id = I("goods_id",'0'); // 商品id
        $consult_type = I("consult_type",'1'); // 商品咨询类型
        $username = I("username",'用户'); // 网友咨询
        $content = I("content"); // 咨询内容

        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'),'consult')) {
            $this->error("验证码错误");
        }

        $goodsConsult = M('goodsConsult');
        if (!$goodsConsult->autoCheckToken($_POST))
        {
                $this->error('你已经提交过了!', U('/Home/Goods/goodsInfo',array('id'=>$goods_id)));
                exit;
        }

        $data = array(
            'goods_id'=>$goods_id,
            'consult_type'=>$consult_type,
            'username'=>$username,
            'content'=>$content,
            'add_time'=>time(),
        );
        $goodsConsult->add($data);
        $this->success('咨询已提交!', U('/Home/Goods/goodsInfo',array('id'=>$goods_id)));
    }

    /**
     * 用户收藏某一件商品
     * @param type $goods_id
     */
    public function collect_goods($goods_id)
    {
        $goods_id = I('goods_id');
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $result = $goodsLogic->collect_goods(cookie('user_id'),$goods_id);
        exit(json_encode($result));
    }

    /**
     * 加入购物车弹出
     */
    public function open_add_cart()
    {
         $this->display();
    }

    //积分商城相关--
    public function integral_mall(){

        $pageNum = 36;
        $filter_param = array(); // 筛选数组
        $id = I('get.id',1); // 当前分类id
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱

        $filter_param['id'] = $id; //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中
//        $integral_goods = M('goods')->where("is_integral=1")->order('goods_id DESC')->select();//首页热卖商品
        $now_time =  time();
        $integral_goods = M('goods')->where("is_integral=1 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0")->select();
//        show_bug($integral_goods);

        $count = count($integral_goods);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('integral_goods',$integral_goods);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        $this->display();
    }

    public function integral_goodsInfo(){
        //  form表单提交
        $now_time =  time();
        C('TOKEN_ON',true);
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $goods_id = I("get.id");
        $goods = M('Goods')->where("goods_id = $goods_id and del = 0")->find();
        if(empty($goods)){
            $this->error('该商品不存在',U('Index/index'));
        }
        if ($goods['store_count']==0) {
            $state['state'] = 1;
            $result = M('Goods')->where("goods_id = $goods_id")->save($state);
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

        //商品是否正在促销中
        if($goods['prom_type'] == 1)
        {
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);
            $flash_sale = M('flash_sale')->where("id = {$goods['prom_id']}")->find();
            $this->assign('flash_sale',$flash_sale);
        }

        $spec_goods_price  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        //热销榜
        $hot = M('goods')->where("is_hot = 1 and cat_id = {$goods['cat_id']}")->order("goods_id desc")->limit(3)->select();
        //推荐
        $recommend = M('goods')->where("is_recommend = 1 and cat_id = {$goods['cat_id']}")->order("goods_id desc")->limit(4)->select();

        if(!empty($goods['linked']))
        {
            //关联商品
            $linkeds = M('goods')->where("linked REGEXP '{$goods['linked']}' and goods_id != {$goods['goods_id']} and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) ")->order("goods_id desc")->limit(6)->select();

            //关联文章
            $articles =  M('article')->where("linked REGEXP '{$goods['linked']}' and is_open = 1 and publish_time<{$now_time}")->order("add_time desc")->limit(4)->select();
        }


        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($goods_id,1));// 面包屑导航
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('hot',$hot);//热销榜
        $this->assign('recommend',$recommend);//店长推荐
        $this->assign('linkeds',$linkeds);//关联商品
        $this->assign('articles',$articles);//关联文章
        $this->assign('goods_attribute',$goods_attribute);//属性值
        $this->assign('goods_attr_list',$goods_attr_list);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('siblings_cate',$goodsLogic->get_siblings_cate($goods['cat_id']));//相关分类
        $this->assign('look_see',$goodsLogic->get_look_see($goods));//看了又看
        $this->assign('goods',$goods);
        $this->display();
    }
    //兑换
    public function exchange(){


        if($this->user_id == 0)
            $this->error('请先登陆',U('Home/User/login'));
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
            $this->error('请先登陆',U('Home/User/login'));
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
	
	
	
	public function divgoodsList(){
		$pageNum = 20;
		$id = tpCache('shop_info.cat_id'); // 当前分类id
		$sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
		$goodsLogic = new \Home\Logic\GoodsLogic();
		$cat_id_arr = getCatGrandson ($id);
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
		 $count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
        }
		
		foreach ($goods_list as $k => &$goods) {
				$result = M('spec_goods_price')->where("goods_id=".$goods['goods_id']."")->find();
				$goods['speckey_name']=$result["key_name"];
				$goods['speckey_id']=$result["key"];
				$speckey_id = explode('_',$goods['speckey_id']);
				$speckey_name = explode(' ',$goods['speckey_name']);
				$showtype="";
				foreach ($speckey_id as $k => $v)
                {
					if(!empty($v))
					{
					 $spec_item = M('spec_item')->where("id=$v")->find();
					 $spec_id = $spec_item['spec_id'];
					 $specs = M('spec')->where("id=$spec_id")->find();
					 if(!empty($spec_item))
					 {
						 if(!empty($spec_item['imgurl']))
						 {
							 $showtype='<img src="__STATIC__/'.$spec_item['imgurl'].'" alt="">'.$showtype;
						 }else
						 {
							 $showtype.='<i>'.$specs['name'].$spec_item['item'].'</i>';
						 }
					 }
					}
				}
				$showtype.='<i>钻重'.$goods['goods_ct'].'</i>';
				$showtype.='<i>美钻价￥'.$goods['shop_price'].'</i>';
				$goods['showtype']=$showtype;
        }
		$this->assign('specv1',$specv1);
		$this->assign('filter_spec1',$filter_spec1);  // 筛选规格
		$this->assign('filter_spec2',$filter_spec2);  // 筛选规格
		$this->assign('goods_list',$goods_list);  // 筛选规格
		 $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        $this->display();
    }
	
	
	 /**
     * 商品列表页
     */
    public function divgoodsList2(){

        $pageNum = 20;
        $filter_param = array(); // 筛选数组
        $id = tpCache('shop_info.jie_id');
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        if($id){
            $filter_param['id'] = $id; //加入筛选条件中
            // $goods_category_tree = M('goods_category')->where("is_show = 1")->order('id asc')->find();
            // $id = $goods_category_tree['id'];
        }

        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类

        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id = $id")->find();// 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
		$now_time =  time();
        $filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);

        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'divgoodsList2'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'divgoodsList2'); // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'divgoodsList2',1); // 获取指定分类下的筛选品牌
        $filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'divgoodsList2',1); // 获取指定分类下的筛选规格
        $filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'divgoodsList2',1); // 获取指定分类下的筛选属性

        $count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
        }
        // print_r($filter_menu);
		if(tpCache('shop_info.product_show')){
		    unset($goods_list);
		}
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        $navigate_cat = $id?navigate_goods($id):array('全部商品'); // 面包屑导航
        $this->assign('goods_list',$goods_list);
        $this->assign('navigate_cat',$navigate_cat);
        $this->assign('goods_category',$goods_category);
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_spec',$filter_spec);  // 筛选规格
        $this->assign('filter_attr',$filter_attr);  // 筛选属性
        $this->assign('filter_brand',$filter_brand);  // 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('goodsCate',$goodsCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        C('TOKEN_ON',false);
        $html = $this->fetch();
        S($key,$html);
        echo $html;
    }
	
	
	/**
     * 商品列表页
     */
    public function divgoodsList3(){

        $pageNum = 20;
        $filter_param = array(); // 筛选数组
        $id = tpCache('shop_info.zhui_id');
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        if($id){
            $filter_param['id'] = $id; //加入筛选条件中
            // $goods_category_tree = M('goods_category')->where("is_show = 1")->order('id asc')->find();
            // $id = $goods_category_tree['id'];
        }

        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类

        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id = $id")->find();// 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
		$now_time =  time();
        $filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);

        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'divgoodsList3'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'divgoodsList3'); // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'divgoodsList3',1); // 获取指定分类下的筛选品牌
        $filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'divgoodsList3',1); // 获取指定分类下的筛选规格
        $filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'divgoodsList3',1); // 获取指定分类下的筛选属性

        $count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id in (".  implode(',', $filter_goods_id2).")")->cache(true)->select();
        }
        // print_r($filter_menu);
		if(tpCache('shop_info.product_show')){
		    unset($goods_list);
		}
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        $navigate_cat = $id?navigate_goods($id):array('全部商品'); // 面包屑导航
        $this->assign('goods_list',$goods_list);
        $this->assign('navigate_cat',$navigate_cat);
        $this->assign('goods_category',$goods_category);
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_spec',$filter_spec);  // 筛选规格
        $this->assign('filter_attr',$filter_attr);  // 筛选属性
        $this->assign('filter_brand',$filter_brand);  // 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('goodsCate',$goodsCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        C('TOKEN_ON',false);
        $html = $this->fetch();
        S($key,$html);
        echo $html;
    }
	
	public function ajaxshowdiy(){
		$id = tpCache('shop_info.cat_id'); // 当前分类id
        $key = I('get.key',0);
		$pkey = I('get.pkey',0);
		$slider1 = I('get.slider1',false);
		$slider2 = I('get.slider2',false);
		$sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
		$price = str_replace(";","-",$slider2); 
		$ct = str_replace(";","-",$slider1); 
		$showtype = I('get.showtype',false);
		//$spec = $showtype;
		if($key>0)
		{
			$iupdate=false;
			if($showtype)
			{
			  $showtypearr=explode('@',$showtype);
			  foreach ($showtypearr as $k => $v)
			  {
				 
				  $specarr=explode('_',$v);
				  if($specarr && $specarr[0]==$pkey)
				  {
					   
					   if(!empty($spec))
					   {
					       $spec = $spec."@".$pkey."_".$key;
					   }else
					   {
                          $spec = $pkey."_".$key;
						  }
					  $iupdate=true;
				  }else
				  {
					  if(!empty($spec))
					   {
					       $spec = $spec."@".$v;
					   }else
					   {
                          $spec = $v;
					   }
					   
				  }
			  }
			}else
			{
				$iupdate=true;
			   $spec = $pkey."_".$key;
			}
			if(!$iupdate)
			{
				$spec = $spec."@".$pkey."_".$key;
			}
		}
		if($id){
            $filter_param['id'] = $id; //加入筛选条件中
        }
		$spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
		$price  && ($filter_param['price'] = $price); //加入筛选条件中
		$ct  && ($filter_param['ct'] = $price); //加入筛选条件中
		$goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
		 // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
		$now_time =  time();
		
        $filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);

        // 过滤筛选的结果集里面找商品
        if($price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice("",$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
		// 过滤筛选的结果集里面找商品
        if($ct)//裸钻CT
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByCt($ct); // 根据CT范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
		
		$count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id in (".  implode(',', $filter_goods_id).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
        }
		foreach ($goods_list as $k => &$goods) {
				$result = M('spec_goods_price')->where("goods_id=".$goods['goods_id']."")->find();
				$goods['speckey_name']=$result["key_name"];
				$goods['speckey_id']=$result["key"];
				$speckey_id = explode('_',$goods['speckey_id']);
				$speckey_name = explode(' ',$goods['speckey_name']);
				$showtype="";
				foreach ($speckey_id as $k => $v)
                {
					
					 $spec_item = M('spec_item')->where("id=$v")->find();
					 $spec_id = $spec_item['spec_id'];
					 $specs = M('spec')->where("id=$spec_id")->find();
					 if(!empty($spec_item))
					 {
						 if(!empty($spec_item['imgurl']))
						 {
							 $showtype='<img src="__STATIC__/'.$spec_item['imgurl'].'" alt="">'.$showtype;
						 }else
						 {
							 $showtype.='<i>'.$specs['name'].$spec_item['item'].'</i>';
						 }
					 }
				}
				$showtype.='<i>钻重'.$goods['goods_ct'].'</i>';
				$showtype.='<i>美钻价￥'.$goods['shop_price'].'</i>';
				$goods['showtype']=$showtype;
			}
		$this->assign('goods_list',$goods_list);  // 筛选规格
		 $this->assign('page',$page);// 赋值分页输出
        $this->assign('count',$count);// 总数
        $this->assign('pageNum',$pageNum);// 每页数
        $this->display();
    }
	
	public function showdiy(){
		$id = tpCache('shop_info.cat_id'); // 当前分类id
        $key = I('get.key',0);
		$pkey = I('get.pkey',0);
		$slider1 = I('get.slider1',false);
		$slider2 = I('get.slider2',false);
		
		$price = str_replace(";","-",$slider2); 
		$ct = str_replace(";","-",$slider1); 
		$showtype = I('get.showtype',false);
		//$spec = $showtype;
		if($key>0)
		{
			$iupdate=false;
			if($showtype)
			{
			  $showtypearr=explode('@',$showtype);
			  foreach ($showtypearr as $k => $v)
			  {
				 
				  $specarr=explode('_',$v);
				  if($specarr && $specarr[0]==$pkey)
				  {
					   
					   if(!empty($spec))
					   {
					       $spec = $spec."@".$pkey."_".$key;
					   }else
					   {
                          $spec = $pkey."_".$key;
						  }
					  $iupdate=true;
				  }else
				  {
					  if(!empty($spec))
					   {
					       $spec = $spec."@".$v;
					   }else
					   {
                          $spec = $v;
					   }
					   
				  }
			  }
			}else
			{
				$iupdate=true;
			   $spec = $pkey."_".$key;
			}
			if(!$iupdate)
			{
				$spec = $spec."@".$pkey."_".$key;
			}
		}
		if($id){
            $filter_param['id'] = $id; //加入筛选条件中
        }
		$spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
		$price  && ($filter_param['price'] = $price); //加入筛选条件中
		$ct  && ($filter_param['ct'] = $price); //加入筛选条件中
		$goodsLogic = new \Home\Logic\GoodsLogic(); // 前台商品操作逻辑类
		 // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
		$now_time =  time();
		
        $filter_goods_id = M('goods')->where("is_integral=0 and is_on_sale=1 and (on_time=0 or on_time<$now_time) and (down_time=0 or down_time>$now_time) and del = 0 and cat_id in(".  implode(',', $cat_id_arr).")")->cache(true)->getField("goods_id",true);

        // 过滤筛选的结果集里面找商品
        if($price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice("",$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
		// 过滤筛选的结果集里面找商品
        if($ct)//裸钻CT
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByCt($ct); // 根据CT范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
		
		$count = count($filter_goods_id);
        $page = new Page($count,$pageNum);
        $page->setConfig('prev','&nbsp;');
        $page->setConfig('next','&nbsp;');
        if($count > 0)
        {
            $goods_list = M('goods')->where("goods_id in (".  implode(',', $filter_goods_id).")")->order("shop_price asc,goods_ct desc")->limit($page->firstRow.','.$page->listRows)->select();
        }
		$ii=0;
		foreach ($goods_list as $k => &$goods) {
			if($ii==0)
			{
				//$sql = " select * from __PREFIX__spec_goods_price where goods_id=".$goods['goods_id'];
				
				//$Model  = new \Think\Model();
                //$result = $Model->query($sql);
				$result = M('spec_goods_price')->where("goods_id=".$goods['goods_id']."")->find();
				$goods['speckey_name']=$result["key_name"];
				$goods['speckey_id']=$result["key"];
				$speckey_id = explode('_',$goods['speckey_id']);
				$speckey_name = explode(' ',$goods['speckey_name']);
				$showtype="";
				foreach ($speckey_id as $k => $v)
                {
					
					 $spec_item = M('spec_item')->where("id=$v")->find();
					 $spec_id = $spec_item['spec_id'];
					 $specs = M('spec')->where("id=$spec_id")->find();
					 if(!empty($spec_item))
					 {
						 if(!empty($spec_item['imgurl']))
						 {
							 $showtype='<img src="__STATIC__/'.$spec_item['imgurl'].'" alt="">'.$showtype;
						 }else
						 {
							 $showtype.='<i>'.$specs['name'].$spec_item['item'].'</i>';
						 }
					 }
				}
				$showtype.='<i>钻重'.$goods['goods_ct'].'</i>';
				$showtype.='<i>美钻价￥'.$goods['shop_price'].'</i>';
				$goods['showtype']=$showtype;
				$result = $goods;
				break;
			}
        }
		$result['specs'] = $spec;
		exit(json_encode($result));
    }
	
	
	/**
    * 商品详情页
    */
    public function diygoodsInfo(){
		$diytype = I("get.diytype");
		$goods_id = I("get.id");
		$cartLogic = new \Home\Logic\CartLogic();
		$unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
		$reat = tpCache('shop_info.diyp');
        //  form表单提交
		$now_time =  time();
        C('TOKEN_ON',true);
        $goodsLogic = new \Home\Logic\GoodsLogic();
        
        $goods = M('Goods')->where("goods_id = $goods_id and del = 0")->find();
        if(empty($goods)){
            $this->error('该商品不存在',U('Index/index'));
        }
        if ($goods['store_count']==0) {
            $state['state'] = 1;
            $result = M('Goods')->where("goods_id = $goods_id")->save($state);
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

        //商品是否正在促销中
        if($goods['prom_type'] == 1)
        {
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);
            $flash_sale = M('flash_sale')->where("id = {$goods['prom_id']}")->find();
            $this->assign('flash_sale',$flash_sale);
        }

        $spec_goods_price  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        //热销榜
        $hot = M('goods')->where("is_hot = 1 and cat_id = {$goods['cat_id']}")->order("goods_id desc")->limit(3)->select();
        //推荐
        $recommend = M('goods')->where("is_recommend = 1 and cat_id = {$goods['cat_id']}")->order("goods_id desc")->limit(4)->select();

        $goods['gift_link']=unserialize($goods['gift_link']);
		
		$goods_spec = array();
		foreach ($filter_spec as $k => $spec)
                {
					$speci=0;
					foreach ($spec as $k2 => $v2)
					{
						if($speci==0)
						{
						 $goods_spec[$k]=$v2['item_id'];
						}
					}
			    }

		$cartLogic->addCart($goods_id, 1, $goods_spec,$this->session_id,$this->user_id,'yes',$diytype);         // 将商品加入购物车
		 $nowcart = $cartLogic->diycartList($this->user,$this->session_id,0,0); // 选中的商品
		 $diytype1=0;
		 $diytype2=0;
		 $diytype3=0;
		 $diytotal_price=0;
		 $diyname1="";
		 $diyname2="";
		 $diyname3="";
		 $diyname="";
		 foreach ($nowcart['result']['cartList'] as $k => $v)
                {
					if($v['diytype']==1)
					{
						$diytype1=1;
						$diyname1=$v['goods_name'];
					}
					if($v['diytype']==2)
					{
						$diytype2=1;
						$diyname2=$v['goods_name'];
					}
					if($v['diytype']==3)
					{
						$diytype3=1;
						$diyname3=$v['goods_name'];
					}
			    }
		
		if($diytype1==1 && ($diytype2==1 || $diytype3==1))
		{
			$diytotal_price=$nowcart['result']['total_price']['total_fee'];
			if($diytype2==1)
			{
			$diyname = $diyname1."+".$diyname2;
			}
			if($diytype3==1)
			{
			$diyname = $diyname1."+".$diyname3;
			}
		}
		//$diytotal_price = $diytotal_price*$reat;
		
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($goods_id,1));// 面包屑导航
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('hot',$hot);//热销榜
        $this->assign('recommend',$recommend);//店长推荐
        $this->assign('goods_attribute',$goods_attribute);//属性值
        $this->assign('goods_attr_list',$goods_attr_list);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('goods',$goods);
		$this->assign('diytype1',$diytype1);//裸钻是否已经选择
		$this->assign('diytype2',$diytype2);//戒托是否已经选择
		$this->assign('diytype3',$diytype3);//戒托是否已经选择
		$this->assign('diytotal_price',$diytotal_price);//总金额
		$this->assign('price_reat',ceil($diytotal_price*$reat/100));//总金额
		$this->assign('diyname',$diyname);//首付比率
        $this->display();
    }
}