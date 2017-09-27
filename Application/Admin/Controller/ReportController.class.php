<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2015-12-21
 */

namespace Admin\Controller;
use Admin\Logic\GoodsLogic;
use Think\AjaxPage;

class ReportController extends BaseController{
	public $begin;
	public $end;
	public function _initialize(){
        parent::_initialize();
		$timegap = I('timegap');
		$gap = I('gap',7);
		if($timegap){
			$gap = explode(' - ', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
		}else{
			$lastweek = date('Y-m-d',strtotime("-1 month"));//30天前
			$begin = I('begin',$lastweek);
			$end =  I('end',date('Y-m-d'));
		}
		$this->begin = strtotime($begin);
		$this->end = strtotime($end);

		$this->assign('timegap',date('Y-m-d',$this->begin).' - '.date('Y-m-d',$this->end));
		$this->begin = strtotime($begin);
		$this->end = strtotime($end);
	}

	public function index(){
		$now = strtotime(date('Y-m-d'));
		$today['today_amount'] = M('order')->where("add_time>$now AND pay_status=1 or pay_code='cod' and order_status in(1,2,4)")->sum('order_amount');//今日销售总额
		$today['today_order'] = M('order')->where("add_time>$now and pay_status=1 or pay_code='cod'")->count();//今日订单数
		$today['cancel_order'] = M('order')->where("add_time>$now AND order_status=3")->count();//今日取消订单
		$today['sign'] = round($today['today_amount']/$today['today_order'],2);
		$this->assign('today',$today);
		$sql = "SELECT COUNT(*) as tnum,sum(order_amount) as amount,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from __PREFIX__`order`";
		$sql .= " where add_time>$this->begin and add_time<$this->end and pay_status=1 or pay_code='cod' and order_status in(1,2,4) group by gap";
		$res = M()->query($sql);//订单数,交易额

		foreach ($res as $val){
			$arr[$val['gap']] = $val['tnum'];
			$brr[$val['gap']] = $val['amount'];
			$tnum += $val['tnum'];
			$tamount += $val['amount'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);
			$order_arr[] = $tmp_num;
			$amount_arr[] = $tmp_amount;
			$sign_arr[] = $tmp_sign;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}

		$this->assign('list',$list);
		$result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}

	public function saleTop(){
		$sql = "select goods_name,goods_sn,sum(goods_num) as sale_num,sum(goods_num*goods_price) as sale_amount from __PREFIX__order_goods ";
		$sql .=" where is_send = 1 group by goods_id order by sale_amount DESC limit 100";
		$res = M()->cache(true,3600)->query($sql);
		$this->assign('list',$res);
		$this->display();
	}

	public function userTop(){
		$p = I('p',1);
		$start = ($p-1)*20;
		$mobile = I('mobile');
		$email = I('email');
		if($mobile){
			$where =  "and b.mobile='$mobile'";
		}
		if($email){
			$where = "and b.email='$email'";
		}
		$sql = "select count(a.order_id) as order_num,sum(a.order_amount) as amount,a.user_id,b.mobile,b.email from __PREFIX__`order` as a left join __PREFIX__users as b ";
		$sql .= " on a.user_id = b.user_id where a.add_time>$this->begin and a.add_time<$this->end and a.pay_status=1 $where group by user_id order by amount DESC limit $start,20";
		$res = M()->cache(true)->query($sql);
		$this->assign('list',$res);
		if(empty($where)){
			$count = M('order')->where("add_time>$this->begin and add_time<$this->end and pay_status=1")->group('user_id')->count();
			$Page = new \Think\Page($count,20);
			$show = $Page->show();
			$this->assign('page',$show);
		}
		$this->display();
	}

	//销售明细首页
	public function saleList(){
		$cat_id = I('cat_id',0);
		$brand_id = I('brand_id',0);
		$GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        $this->assign('cat_id',$cat_id);
        $this->assign('brand_id',$brand_id);
    	$this->display();
	}

	//销售明细ajax
	public function ajaxSaleList(){
		$condition = array();
		$export = I('get.export');
		$p = I('get.p',1);
		$start = ($p-1)*20;
		$cat_id = I('cat_id',0);
		$brand_id = I('brand_id',0);
		$where = "where b.add_time>$this->begin and b.add_time<$this->end ";
		if($cat_id>0){
			$where .= " and g.cat_id=$cat_id";
			// $this->assign('cat_id',$cat_id);
		}
		if($brand_id>0){
			$where .= " and g.brand_id=$brand_id";
			// $this->assign('brand_id',$brand_id);
		}
		$sql = "select a.*,b.order_sn,b.shipping_name,b.pay_name,b.add_time,g.cat_id from __PREFIX__order_goods as a left join __PREFIX__`order` as b on a.order_id=b.order_id ";
		$sql .= " left join __PREFIX__goods as g on a.goods_id = g.goods_id $where ";
		if($export){
			$sql .= "  order by add_time";
		}else{
			$sql .= "  order by add_time limit $start,20";
		}
		$res = M()->query($sql);
		if($export){
			$this->export($res);	//导出
		}else{
			$this->assign('list',$res);
			$sql2 = "select count(*) as tnum from __PREFIX__order_goods as a left join __PREFIX__`order` as b on a.order_id=b.order_id ";
			$sql2 .= " left join __PREFIX__goods as g on a.goods_id = g.goods_id $where";
			$total = M()->query($sql2);
			$count =  $total[0]['tnum'];
			$Page  = new AjaxPage($count,20);
			$show = $Page->show();
			$this->assign('page',$show);
	    	$this->display();
	    }
	}
	/**
	 * [export 导出销售明细]
	 * @param  [type] $list [要导出的列表]
	 * @return [type]       [excel文件]
	 */
	private function export($list){
		$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">序号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="200">流水号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">类别</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">数量</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">单价</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">金额</td>';
    	$strTable .= '</tr>';
    	foreach($list as $k=>$val){
    		$category = M('goods_category')->where("id = {$val['cat_id']}")->find();
    		$strTable .= '<tr>';
    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$k.'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['add_time']).' </td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">&nbsp;'.$val['order_sn'].' </td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$category['name'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_num'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price']*$val['goods_num'].'</td>';
    		$strTable .= '</tr>';
    	}
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'sales');
    	exit();
	}

	public function user(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$user['today'] = D('users')->where("reg_time>$today")->count();//今日新增会员
		$user['month'] = D('users')->where("reg_time>$month")->count();//本月新增会员
		$user['total'] = D('users')->count();//会员总数
		$user['user_money'] = D('users')->sum('user_money');//会员余额总额
		$res = M('order')->cache(true)->distinct(true)->field('user_id')->select();
		$user['hasorder'] = count($res);
		$this->assign('user',$user);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where reg_time>$this->begin and reg_time<$this->end group by gap";
		$new = M()->query($sql);//新增会员趋势
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}

	//财务统计
	public function finance(){
		$sql = "SELECT sum(a.order_amount) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		$sql .= " FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__`order` a left join __PREFIX__order_goods b on a.order_id=b.order_id ";
		$sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.pay_status=1 and a.order_status in (1,2,4) group by gap order by a.add_time";
		$res = M()->cache(true)->query($sql);//物流费,交易额,成本价

		foreach ($res as $val){
			$arr[$val['gap']] = $val['goods_amount'];
			$brr[$val['gap']] = $val['cost_price'];
			$crr[$val['gap']] = $val['shipping_amount'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$tmp_goods_amount = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
			$tmp_shipping_amount =  empty($crr[date('Y-m-d',$i)]) ? 0 : $crr[date('Y-m-d',$i)];
			$goods_arr[] = $tmp_goods_amount;
			$amount_arr[] = $tmp_amount;
			$shipping_arr[] = $tmp_shipping_amount;
			$date = date('Y-m-d',$i);
			$list[] = array('day'=>$date,'goods_amount'=>$tmp_goods_amount,'cost_amount'=>$tmp_amount,'shipping_amount'=>$tmp_shipping_amount,'end'=>date('Y-m-d',$i+24*60*60));
			$day[] = $date;
		}

		$this->assign('list',$list);
		$result = array('goods_arr'=>$goods_arr,'amount'=>$amount_arr,'shipping_arr'=>$shipping_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}
	
	//流量分析首页
	public function flow(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$flow['today'] = D('click_log')->where("add_time>$today")->count();//今日访问人数和地区及来源网站
		$flow['month'] = D('click_log')->where("add_time>$month")->count();//本月访问人数和地区及来源网站
		$flow['total'] = D('click_log')->count();//访问总数和地区及来源网站
		$flow['total1'] = D('click_log')->where("add_time>$this->begin and add_time<$this->end+24*3600")->count();//时间内访问量
		$this->assign('flow',$flow);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from __PREFIX__click_log where add_time>$this->begin and add_time<$this->end+24*3600 group by gap";
		$new = M()->query($sql);//指定日期情况
		
		$sql1 = "SELECT COUNT(*) as num,city as gap from __PREFIX__click_log where add_time>$this->begin and add_time<$this->end+24*3600 group by gap";
		$city_list = M()->query($sql1);//指定日期情况
		$this->assign('list',$city_list);
		
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}
		
		$sql2 = "SELECT COUNT(*) as num,goin as gap from __PREFIX__click_log where add_time>$this->begin and add_time<$this->end+24*3600 group by gap";
		$goin_list = M()->query($sql2);//指定日期情况
		foreach ($goin_list as $val){
			$brr1[] = $val['num'];
			$day1[] = $val['gap'];
		}
		
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}
		$result = array('data'=>$brr,'time'=>$day,'data1'=>$brr1,'time1'=>$day1);
		$this->assign('result',json_encode($result));
		$this->display();
	}
	
	
	
	//流量分析首页
	public function keyword(){
		$sql2 = "SELECT SUM(conuts) as num,kurl,keyword as gap from __PREFIX__keyword_log where add_time>$this->begin and add_time<$this->end+24*3600 group by kurl,gap";
		$keyword_list = M()->query($sql2);//指定日期情况
		$this->assign('list',$keyword_list);
		$this->display();
	}
	
	
	
	//订单明细列表
	public function orderList(){
        $this->assign('order_status',C('ORDER_STATUS'));
		$order_status = I('order_status');
		$where="";
		if($order_status>0)
		{
			$order_status1 = $order_status-1;
			$where .= " and order_status=$order_status1";
		}
		$sql1 = "SELECT COUNT(*) as num,order_status as gap from __PREFIX__`order` where add_time>$this->begin and add_time<$this->end+24*3600 $where group by gap";
		$city_list = M()->query($sql1);//指定日期情况
		foreach(C('ORDER_STATUS') as $k=>$v)
		{
			$varnu=0;
			foreach ($city_list as $val){
				if($k==$val['gap'])
				{
					$varnu = $val['num'];
				}
		    }
			$brr[] = $varnu;
			$day[] = $v;
		}
		
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('status',$order_status);
		$this->assign('result',json_encode($result));
    	$this->display();
	}
	
	
	
	//会员购物分析
	public function userbuy(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$userbuy['today'] = D('userbuy')->where("reg_time>$today")->count();//今日访问人数和地区及来源网站
		$userbuy['month'] = D('userbuy')->where("reg_time>$month")->count();//本月访问人数和地区及来源网站
		$userbuy['total'] = D('userbuy')->count();//访问总数和地区及来源网站
		$this->assign('userbuy',$userbuy);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__userbuy where reg_time>$this->begin and reg_time<$this->end group by gap";
		$new = M()->query($sql);//指定日期情况
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}

		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));
		$this->display();
	}
	
	//商品销售分析
	public function goodsList(){
    	$this->display();
	}
	
	//订单明细ajax
	public function ajaxGoodsList(){
		$condition = array();
		$export = I('get.export');
		$p = I('get.p',1);
		$start = ($p-1)*20;
		if(!empty($title))
		{
		  $where = " and title like '%$title%'";
		}
		
		$sql = "select * from __PREFIX__goods $where ";
		if($export){
			$sql .= "  order by add_time";
		}else{
			$sql .= "  order by add_time limit $start,20";
		}
		$res = M()->query($sql);
		$this->assign('list',$res);
		$sql2 = "select count(*) from __PREFIX__goods $where";
		$total = M()->query($sql2);
		$count =  $total[0]['tnum'];
		$Page  = new AjaxPage($count,20);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->display();
	}

}