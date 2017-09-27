<?php
/**
 * 商品批量上传处理
 * User: huangxiansheng
 * Date: 16/8/18
 * Time: 下午8:56
 */

namespace Admin\Controller;
use Common\Util\Excel;
class GoodsuploadController extends BaseController{

    public function readExcel(){
        $data = Excel::excel2array('./Public/goods.csv');
        var_dump($data);
    }

}
