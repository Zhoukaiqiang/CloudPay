<?php

namespace app\admin\controller;

use think\Controller;
use think\Loader;
use think\Request;

class Excel extends Controller
{
    /**
     * 导出excel表
     *
     * @return \think\Response
     */
    public function excel()
    {
        echo dirname(__FILE__);die;
        Loader::import('Classes/PHPExcel/IOFactory',EXTEND_PATH);
        //加载excel文件
        $filename = dirname(__FILE__).'/result.xlsx';
        $objPHPExcelReader = \PHPExcel_IOFactory::load($filename);

        $sheet = $objPHPExcelReader->getSheet(0); 		// 读取第一个工作表(编号从 0 开始)
        $highestRow = $sheet->getHighestRow(); 			// 取得总行数
        $highestColumn = $sheet->getHighestColumn(); 	// 取得总列数

        $arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M', 'N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        // 一次读取一列
        $res_arr = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            $row_arr = array();
            for ($column = 0; $arr[$column] != 'F'; $column++) {
                $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                $row_arr[] = $val;
            }

            $res_arr[] = $row_arr;
        }

        return $res_arr;

    }


}
