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


// 微信支付相关funciton
 function create_noncestr($length = 16){
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i ++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}
/* 签名算法 */
function getSign($Obj,$key)
{
    foreach ($Obj as $k => $v) {
        $Parameters[strtolower($k)] = $v;
    }
    // 签名步骤一：按字典序排序参数
    ksort($Parameters);
    $String = formatBizQueryParaMap($Parameters, false);
    // echo "【string】 =".$String."</br>";
    // 签名步骤二：在string后加入KEY
    $String = $String . "&key=" . $key;
    // echo "【string】 =".$String."</br>";
    // 签名步骤三：MD5加密
    $result_ = strtoupper(md5($String));
    return $result_;
}
// 把数组中的元素根据大小写进行排序,签名算法中有要求
function formatBizQueryParaMap($paraMap, $urlencode)
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
function arrayToXml($arr)
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
function postXmlCurl($xml, $url)
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
function xmlToArray($xml)
{
    // 将XML转为array
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}

 function getSet()
{

    $set=M('sign_set')->find();
    if (empty($set)) {
        return '';
    }

    return $set;
}
//带证书的数据提交
function curl_post_ssl($url, $vars, $second = 30)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSLCERT, './plugins/payment/weixin/cert/apiclient_cert.pem');
//    curl_setopt($ch, CURLOPT_SSLCERT, './cert/apiclient_cert.pem');
    curl_setopt($ch, CURLOPT_SSLKEY, './plugins/payment/weixin/cert/apiclient_key.pem');
//    curl_setopt($ch, CURLOPT_SSLKEY, './cert/apiclient_key.pem');
    curl_setopt($ch, CURLOPT_CAINFO, './plugins/payment/weixin/cert/rootca.pem');
//    curl_setopt($ch, CURLOPT_CAINFO, './cert/rootca.pem');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
    $data = curl_exec($ch);

    if ($data) {
        curl_close($ch);
        return $data;
    } else {
        $error = curl_errno($ch);
        logResult1('Errno' . $error); // 捕抓异常
        echo "";
        curl_close($ch);
        return false;
    }
}
function getMonth()
{
    $month = array();
    $start_year = '2016';
    $start_month = '8';
    $this_year = date('Y', time());
    $this_month = date('m', time());
    $i = $start_year;

    while ($i <= $this_year) {
        if (0 < ($this_year - $i)) {
            $ii_month = 12;
        }
        else {
            $ii_month = $this_month;
        }

        if ($start_year < $i) {
            $start_month = 1;
        }


        $ii = $start_month;

        while ($ii <= $ii_month) {
            $month[] = array('year' => $i, 'month' => ($ii < 10 ? '0' . $ii : $ii));
            ++$ii;
        }

        ++$i;
    }

    return $month;
}
function getCalendar($user_id,$year = NULL, $month = NULL, $week = true)
{
    if (empty($year)) {
        $year = date('Y', time());
    }
    if (empty($month)) {
        $month = date('m', time());
    }
    $set = getSet();
    $date = getDate1(array('year' => $year, 'month' => $month));
    $array = array();
    $maxday = 28;
    if (28 < $date['days']) {
        $maxday = 35;
    }
    $i = 1;
    while ($i <= $maxday) {
        $day = 0;
        if ($i <= $date['days']) {
            $day = $i;
        }
        $today = 0;
        if (($date['thisyear'] == $year) && ($date['thismonth'] == $month) && ($date['doday'] == $i)) {
            $today = 1;
        }
        $array[$i] = array('year' => $date['year'], 'month' => $date['month'], 'day' => $day, 'date' => $date['year'] . '-' . $date['month'] . '-' . $day, 'signed' => 0, 'signold' => 1, 'title' => '', 'today' => $today);
        ++$i;
    }
//    $records = pdo_fetchall('select *  from ' . tablename('ewei_shop_sign_records') . ' where openid=:openid and `type`=0 and `time` between :starttime and :endtime', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':starttime' => $date['firsttime'], ':endtime' => $date['lasttime']));
    $where['user_id']=$user_id;
    $where['type']=0;
    $where['time']=array('between',array("$date[firsttime]","$date[lasttime]"));
    $records=M('sign_records')->where($where)->select();
    if (!empty($records)) {
        foreach ($records as $item ) {
            $sign_date = array('year' => date('Y', $item['time']), 'month' => date('m', $item['time']), 'day' => date('d', $item['time']));
            foreach ($array as $day => &$row ) {
                if ($day == $sign_date['day']) {
                    $row['signed'] = 1;
                }
            }
            unset($row);
        }
    }
//    $reword_special = unserialize($set['reword_special']);
//
//    if (!empty($reword_special)) {
//        foreach ($reword_special as $item ) {
//            $sign_date = array('year' => date('Y', $item['date']), 'month' => date('m', $item['date']), 'day' => date('d', $item['date']));
//
//            foreach ($array as $day => &$row ) {
//                if (($row['day'] == $sign_date['day']) && ($row['month'] == $sign_date['month']) && ($row['year'] == $sign_date['year'])) {
//                    $row['title'] = $item['title'];
//                    $row['color'] = $item['color'];
//                }
//
//            }
//
//            unset($row);
//        }
//    }
    if ($week) {
        $calendar = array();
        foreach ($array as $index => $row ) {
            if ((1 <= $index) && ($index <= 7)) {
                $cindex = 0;
            }
            else if ((8 <= $index) && ($index <= 14)) {
                $cindex = 1;
            }
            else if ((15 <= $index) && ($index <= 21)) {
                $cindex = 2;
            }
            else if ((22 <= $index) && ($index <= 28)) {
                $cindex = 3;
            }
            else if ((29 <= $index) && ($index <= 35)) {
                $cindex = 4;
            }
            $calendar[$cindex][] = $row;
        }
    }
    else {
        $calendar = $array;
    }

    return $calendar;
}

function getsign1($user_id,$date = NULL)
{
    $set = getSet();
    $condition = '';

    if (!empty($set['cycle'])) {
        $month_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $month_end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $condition .= ' and `time` between ' . $month_start . ' and ' . $month_end . ' ';
    }


//    $records = pdo_fetchall('select * from ' . tablename('ewei_shop_sign_records') . ' where openid=:openid and `type`=0 ' . $condition . ' order by `time` desc ', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
    $where=" user_id=$user_id and type =0 ".$condition;
    $records=M('sign_records')->where($where)->order('time desc')->select();
//    var_dump($records);
    $sum = 0;
    $signed = 0;
    $orderindex = 0;
    $yesterday = 0;
    $order = array();
    $orderday = 0;

    if (!empty($records)) {
        foreach ($records as $item ) {
            ++$sum;
            $day = date('Y-m-d', $item['time']);
            $today = date('Y-m-d', time());
            if (empty($date) && ($day == $today)) {
                $signed = 1;
            }
            if (!empty($date) && ($day == $date)) {
                $signed = 1;
            }
            $dday = date('d', $item['time']);
            if (($yesterday - 1) == $dday) {
                $yesterday = $dday;
            }
            else {
                $yesterday = $dday;
                ++$orderindex;
            }
            ++$order[$orderindex];
            if (dateplus($day, $orderday) == dateminus($today, 1)) {
                ++$orderday;
            }
        }
    }

    $data = array('order' => (empty($order) ? 0 : max($order)), 'orderday' => (empty($signed) ? $orderday : $orderday + 1), 'sum' => $sum, 'signed' => $signed);
    return $data;
}



function getDate1($date = array())
{
    if (empty($date)) {
        $date = array('year' => date('y', time()), 'month' => date('m', time()), 'day' => date('d', time()));
    }

    $days = date('t', strtotime($date['year'] . '-' . $date['month']));
    $result = array('firstday' => 1, 'lastday' => $days, 'firsttime' => strtotime($date['year'] . '-' . $date['month'] . '-1'), 'lasttime' => strtotime($date['year'] . '-' . ($date['month'] + 1) . '-1') - 1, 'year' => $date['year'], 'thisyear' => date('Y', time()), 'month' => $date['month'], 'thismonth' => date('m', time()), 'day' => $date['day'], 'doday' => date('d', time()), 'days' => $days);
    return $result;
}

function dateplus($date, $day)
{
    $time = strtotime($date);
    $time = $time + (3600 * 24 * $day);
    $date = date('Y-m-d', $time);
    return $date;
}

 function dateminus($date, $day)
{
    $time = strtotime($date);
    $time = $time - (3600 * 24 * $day);
    $date = date('Y-m-d', $time);
    return $date;
}

function getAdvAward($user_id)
{
    global $_W;
    $set = getSet();
    $date = getDate1();
    $signinfo = getsign1($user_id);
    $reword_sum = unserialize($set['reword_sum']);
    $reword_order = unserialize($set['reword_order']);
    $condition = '';

    if (!empty($set['cycle'])) {
        $month_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $month_end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $condition .= ' and `time` between ' . $month_start . ' and ' . $month_end . ' ';
    }


//    $records = pdo_fetchall('select * from ' . tablename('ewei_shop_sign_records') . ' where openid=:openid and uniacid=:uniacid ' . $condition . ' order by `time` asc ', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
    $where=" user_id=$user_id  ".$condition;
    $records=M('sign_records')->where($where)->order('time desc')->select();
    if (!empty($records)) {
        foreach ($records as $item ) {
            if (!empty($reword_order)) {
                foreach ($reword_order as $i => &$order ) {
                    if (!empty($set['cycle']) && ($date['days'] < $order['day'])) {
                        unset($reword_order[$i]);
                    }
                    if (($item['day'] == $order['day']) && ($item['type'] == 1)) {
                        $order['drawed'] = 1;
                    }
                    else if ($order['day'] <= $signinfo['order']) {
                        $order['candraw'] = 1;
                    }
                }
                unset($order);
            }
            if (!empty($reword_sum)) {
                foreach ($reword_sum as $i => &$sum ) {
                    if (!empty($set['cycle']) && ($date['days'] < $sum['day'])) {
                        unset($reword_sum[$i]);
                    }
                    if (($item['day'] == $sum['day']) && ($item['type'] == 2)) {
                        $sum['drawed'] = 1;
                    }
                    else if ($sum['day'] <= $signinfo['sum']) {
                        $sum['candraw'] = 1;
                    }
                }
                unset($sum);
            }
        }
    }
    $data = array('order' => $reword_order, 'sum' => $reword_sum);
    return $data;

}

function updateSign($user_id,$signinfo)
{
    global $_W;

    if (empty($signinfo)) {
        $signinfo = getsign1($user_id);
    }


//    $info = pdo_fetch('select id  from ' . tablename('ewei_shop_sign_user') . ' where openid=:openid and uniacid=:uniacid limit 1 ', array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
    $info=M('sign_user')->where(array('user_id'=>$user_id))->find();

    $data = array('user_id' =>$user_id, 'order' => $signinfo['order'], 'orderday' => $signinfo['orderday'], 'sum' => $signinfo['sum'], 'signdate' => date('Y-m'));

    if (empty($info)) {

//        pdo_insert('ewei_shop_sign_user', $data);
        M('sign_user')->add($data);
        return NULL;
    }

    M('sign_user')->where(array('id'=>$info['id']))->save($data);
//    pdo_update('ewei_shop_sign_user', $data, array('id' => $info['id']));
}