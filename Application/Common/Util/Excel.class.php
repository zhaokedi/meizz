<?php
/**
 * excel相关操作类
 * 此类依赖phpexcel
 * User: huangxiansheng
 * Date: 16/8/20
 * Time: 下午8:17
 */

namespace Common\Util;
import('ORG.PHPExcel.Classes.PHPExcel','','.php');
class Excel{

    /**
     * excel转数组(支持xlsx/xls/csv)
     * @param $filePath
     * @param int $sheetpage
     * @param string $charset
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    static public function excel2array($filePath, $sheetpage = 0 , $charset = 'UTF8'){

        $type = strtolower( pathinfo($filePath, PATHINFO_EXTENSION) );

        if (!file_exists($filePath)) {
            die('no file!');
        }

        //根据不同类型分别操作
        if( $type=='xlsx'||$type=='xls' ){
            $objPHPExcel = \PHPExcel_IOFactory::load($filePath);
        }else if( $type=='csv' ){
            $objReader = \PHPExcel_IOFactory::createReader('CSV')
                ->setDelimiter(',')
                ->setInputEncoding($charset) //不设置将导致中文列内容返回boolean(false)或乱码
                ->setEnclosure('"')
                ->setLineEnding("\r\n")
                ->setSheetIndex(0);
            $objPHPExcel = $objReader->load($filePath);

        }else{
            die('Not supported file types!');
        }

        //选择标签页
        $sheet = $objPHPExcel->getSheet($sheetpage);

        //获取行数与列数,注意列数需要转换
        $highestRowNum = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnNum = \PHPExcel_Cell::columnIndexFromString($highestColumn);

        //取得字段，这里测试表格中的第一行为数据的字段，因此先取出用来作后面数组的键名
        $filed = array();
        for($i=0; $i<$highestColumnNum;$i++){
            $cellName = \PHPExcel_Cell::stringFromColumnIndex($i).'1';
            $cellVal = $sheet->getCell($cellName)->getValue();//取得列内容
            $filed []= $cellVal;
        }

        //开始取出数据并存入数组
        $data = array();
        for($i=2;$i<=$highestRowNum;$i++){//ignore row 1
            $row = array();
            for($j=0; $j<$highestColumnNum;$j++){
                $cellName = \PHPExcel_Cell::stringFromColumnIndex($j).$i;
                $cellVal = $sheet->getCell($cellName)->getValue();
                $row[ $filed[$j] ] = $cellVal;
            }
            $data []= $row;
        }
        return $data;
        //完成，可以存入数据库了
    }
}