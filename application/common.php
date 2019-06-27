<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * print
 * @param string $data
 */
function v($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    die;
}

/**
 * format 保留两位小数
 * @param float $input
 */
function format($input, $number = 2)
{
    return sprintf("%." . $number . "f", $input);
}

/**
 * 微信昵称特殊处理
 * @param string $str
 */
function removeEmoji($str) 
{
    $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

     return $str;
}

/**
 * Curl Get
 * @param string $url  请求地址
 */
function curlGet($url)
{
    $ch = curl_init();  
    curl_setopt($ch,CURLOPT_URL,$url);  
    curl_setopt($ch,CURLOPT_HEADER,0);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );  
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $res = curl_exec($ch);
    curl_close($ch); 
    $result = trim($res, "\xEF\xBB\xBF");
    return $result; 
}

/** 
 * Curl Post请求
 * @param string $url       请求地址
 * @param string $data      请求数据
 * @param string $dataType  请求类型
 */
function curlPost($url, $data = '', $dataType = '')
{
    $dataTypeArr = [
        'form' => ['content-type: application/x-www-form-urlencoded;charset=UTF-8'],
        'json' => ['Content-Type: application/json;charset=utf-8'],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    if(!empty($dataType))
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $dataTypeArr[$dataType]);
    }
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    $result = trim($result, "\xEF\xBB\xBF");
    return $result;
}

/**
 * 实例化Redis
 */
function redis()
{
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    return $redis;
}

/**
 * 无限级节点归类(替代递归)
 * @param array $list       归类数组
 * @param string $id 	    父级ID
 * @param string $pid 		父级PID
 * @param string $child 	子级归类后的key，默认child
 * @param string $root 		顶级
 */
function tree($list, $pk = 'id', $pid = 'pid', $child = 'child', $root = 0)
{  
    $tree = [];

    if(is_array($list)) 
    {  
        $refer = [];

        //基于数组的指针(引用) 并 同步改变数组
        foreach ($list as $key => $val) 
        {  
            $refer[$val[$pk]] = &$list[$key];
        }

        // v($refer);

        foreach ($list as $key => $val)
        {  
            //判断是否存在parent
            $parentId = isset($val[$pid]) ? $val[$pid] : $root;

            if ($root == $parentId) 
            {  
                $tree[$val[$pk]] = &$list[$key]; 
            }
            else
            {  
                if (isset($refer[$parentId]))
                {  
                    $refer[$parentId][$child][] = &$list[$key];  
                    // v($refer[$parentId]);
                }  
            }
        } 
    }

    return $tree;  
}

/**
 * 导出excel表格
 * @param array   $columName  第一行的列名称
 * @param array   $list       二维数组
 * @param string  $setTitle   sheet名称
 */
function exportExcel($columName, $list, $fileName='demo', $setTitle = 'Sheet1')
{
    if ( empty($columName) || empty($list) ) 
    {
        return '列名或者内容不能为空';
    }
    
    if ( count($list[0]) != count($columName) ) 
    {
        return '列名跟数据的列不一致';
    }
    
    //实例化PHPExcel类
    $PHPExcel = new PHPExcel();

    //获得当前sheet对象
    $PHPSheet = $PHPExcel->getActiveSheet();

    //定义sheet名称
    $PHPSheet->setTitle($setTitle);
    
    //Excel列
    $letter = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M',
        'N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
    ];

    //把列名写入第1行
    for ($i = 0; $i < count($list[0]); $i++) 
    {
        //$letter[$i]1 = A1 B1 C1  $letter[$i] = 列1 列2 列3

        // 第一行加粗
        $PHPSheet->getStyle($letter[$i] . '1')->getFont()->setBold(true);

        // 设置值
        $PHPSheet->setCellValue("$letter[$i]1","$columName[$i]");
    }

    //内容第2行开始
    foreach ($list as $key => $val) 
    {
        //array_values 把一维数组的键转为0 1 2 ..
        foreach (array_values($val) as $key2 => $val2) 
        {
            //$letter[$key2].($key+2) = A2 B2 C2 ……
            $PHPSheet->setCellValue($letter[$key2].($key+2),$val2);
        }
    }

    //生成2007版本的xlsx
    $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename='.$fileName.'.xlsx');
    header('Cache-Control: max-age=0');
    $PHPWriter->save("php://output");
}
