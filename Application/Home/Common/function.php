<?php
function send_email1($subject='',$content='',$to=false){
	require_once THINK_PATH.'Library\Vendor\phpmailer\PHPMailerAutoload.php';
	$mail = new PHPMailer;
	$config = tpCache('smtp');
	$mail->isSMTP();
	//Enable SMTP debugging
	// 0 = off (for production use)
	// 1 = client messages
	// 2 = client and server messages
	$mail->SMTPDebug = 0;
	//调试输出格式
	//$mail->Debugoutput = 'html';
	//smtp服务器
	$mail->Host = $config['smtp_server'];
	//端口 - likely to be 25, 465 or 587
	$mail->Port = $config['smtp_port'];
	//Whether to use SMTP authentication
	$mail->SMTPAuth = true;
	//用户名
	$mail->Username = $config['smtp_user'];
	//密码
	$mail->Password = $config['smtp_pwd'];
	//Set who the message is to be sent from
	$mail->setFrom($config['smtp_user']);
	//回复地址
	//$mail->addReplyTo('replyto@example.com', 'First Last');
	//接收邮件方
	if($to==false){
		$to= $config['smtp_get'];
	}
	$mail->addAddress($to);
	//标题
	$mail->Subject = $subject;
	//HTML内容转换
	$mail->msgHTML($content);
	//Replace the plain text body with one created manually
	//$mail->AltBody = 'This is a plain-text message body';
	//添加附件
	//$mail->addAttachment('images/phpmailer_mini.png');
	//send the message, check for errors
	if (!$mail->send()) {
		return false;
	} else {
		return true;
	}

}
/**
 * 验证码发送
 * @param $mobile 手机号码
 * @param $content 发送内容
 * @param $type 验证码类型
 */
function send_sms($mobile,$content,$type=''){

}


/**
 * 面包屑导航  用于前台用户中心
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_user()
{    
    $navigate = include APP_PATH.'Common/Conf/navigate.php';    
    $location = strtolower('Home/'.CONTROLLER_NAME);
    $arr = array(
        '首页'=>'/',
        $navigate[$location]['name']=>U('/Home/'.CONTROLLER_NAME),
        $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();',
    );
    return $arr;
}


/**
 * ip地址解析
 * 根据当前的IP获取地址
 */
function ip_adds($ip)
{    
   //$ip_adds = @file_get_contents("http://ip.taobao.com/service/getIpInfo.php?ip=".$ip);
   //$obj=json_decode($ip_adds); 
   $ip_adds = @file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$ip);
   if(empty($ip_adds))
   {
	   return "未知";
   }
   else
   {
   $obj=json_decode($ip_adds); 
   return $obj->city;
   }
}



/**
 * 来源页面解析
 * 根据父路径解析来源网址
 */
function rtferer($url)
{  
   $rtferer="self";
   if(empty($url))
   {
	   $rtferer="手动输入";
   }else
   {
	 if(!strstr($url,$_SERVER['HTTP_HOST']))
	 {   
	   if(strstr($url,"baidu.com"))
	   {
		   $rtferer="百度";
	   }elseif(strstr($url,"google.com.hk"))
	   {
		   $rtferer="谷歌";
	   }elseif(strstr($url,"yahoo.cn"))
	   {
		   $rtferer="雅虎";
	   }elseif(strstr($url,"sogou.com"))
	   {
		   $rtferer="搜狗";
	   }elseif(strstr($url,"soso.com"))
	   {
		   $rtferer="搜搜";
	   }elseif(strstr($url,"bing.com"))
	   {
		   $rtferer="必应";
	   }elseif(strstr($url,"youdao.com"))
	   {
		   $rtferer="有道";
	   }else
	   {
		   $rtferer="其他";
	   }
	 }
   }
   return $rtferer;
}



//获取来自搜索引擎入站时的关键词 
function get_keyword($url) 
{ 
	//$url=isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';//获取入站url。 
	$search_1="google.com"; //q= utf8 
	$search_2="baidu.com"; //wd= gbk 
	$search_3="yahoo.cn"; //q= utf8 
	$search_4="sogou.com"; //query= gbk 
	$search_5="soso.com"; //w= gbk 
	$search_6="bing.com"; //q= utf8 
	$search_7="youdao.com"; //q= utf8 
	
	$google=preg_match("/\b{$search_1}\b/",$url);//记录匹配情况，用于入站判断。 
	$baidu=preg_match("/\b{$search_2}\b/",$url); 
	$yahoo=preg_match("/\b{$search_3}\b/",$url); 
	$sogou=preg_match("/\b{$search_4}\b/",$url); 
	$soso=preg_match("/\b{$search_5}\b/",$url); 
	$bing=preg_match("/\b{$search_6}\b/",$url); 
	$youdao=preg_match("/\b{$search_7}\b/",$url); 
	$urlname="";
	$s_s_keyword=""; 
	//获取没参数域名 
	preg_match('@^(?:http://)?([^/]+)@i',$url,$matches); 
	$burl=$matches[1]; 
	//匹配域名设置 
	$curl=$_SERVER['HTTP_HOST'];
	if($burl!=$curl){ 
		if ($google) 
		{//来自google 
			$s_s_keyword=showkoword($url,'q=');//关键词前的字符为"q="。
			$s_s_keyword=urldecode($s_s_keyword); 
			$urlname="谷歌"; 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
		} 
		else if($baidu) 
		{//来自百度 
			$s_s_keyword=showkoword($url,'wd=');//关键词前的字符为"wd="。 
			$s_s_keyword=urldecode($s_s_keyword); 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
			$urlname="百度"; 
		} 
		else if($yahoo) 
		{//来自雅虎 
			$s_s_keyword=showkoword($url,'q=');//关键词前的字符为"q="。 
			$s_s_keyword=urldecode($s_s_keyword); 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
			$urlname="雅虎"; 
		} 
		else if($sogou) 
		{//来自搜狗 
			$s_s_keyword=showkoword($url,'query=');//关键词前的字符为"query="。 
			$s_s_keyword=urldecode($s_s_keyword); 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
			$urlname="搜狗"; 
		} 
		else if($soso) 
		{//来自搜搜 
			$s_s_keyword=showkoword($url,'w=');//关键词前的字符为"w="。 
			$s_s_keyword=urldecode($s_s_keyword); 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
			$urlname="搜搜"; 
		} 
		else if($bing) 
		{//来自必应 
			$s_s_keyword=showkoword($url,'q=');//关键词前的字符为"q="。 
			$s_s_keyword=urldecode($s_s_keyword); 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
			$urlname="必应"; 
		} 
		else if($youdao) 
		{//来自有道 
			$s_s_keyword=showkoword($url,'q=');//关键词前的字符为"q="。 
			$s_s_keyword=urldecode($s_s_keyword); 
			//$s_s_keyword=iconv("GBK","UTF-8",$s_s_keyword);//引擎为gbk 
			$urlname="有道"; 
		} 
	} 
    return $urlname."&".$s_s_keyword; 
}

/**
*  面包屑导航  用于前台商品
 * @param type $id 商品id  或者是 商品分类id
 * @param type $type 默认0是传递商品分类id  id 也可以传递 商品id type则为1
 */
function navigate_goods($id,$type = 0)
{
    $cat_id = $id; //
    // 如果传递过来的是
    if($type == 1){
        $cat_id = M('goods')->where("goods_id = $id")->getField('cat_id');
    }
    $categoryList = M('GoodsCategory')->getField("id,name,parent_id");

    // 第一个先装起来
    $arr[$cat_id] = $categoryList[$cat_id]['name'];
    while (true)
    {
        $cat_id = $categoryList[$cat_id]['parent_id'];
        if($cat_id > 0)
            $arr[$cat_id] = $categoryList[$cat_id]['name'];
        else
            break;
    }
    $arr = array_reverse($arr,true);
    return $arr;
}
