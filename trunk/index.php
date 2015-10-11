<?php

header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                                    // HTTP/1.0


if(isset($_GET['PHPSESSID'])) {         // 名为 PHPSESSION 的Cookie session自动生成
    session_id($_GET['PHPSESSID']);
}
// 根据环境不同引入不同的配置文件
$env = 'dev';
if ( is_file( dirname(__FILE__).'/../../online' ) ){
    $env = 'online';
}elseif( is_file( dirname(__FILE__).'/../../sandbox' ) ){
    $env = 'sandbox';
}

require_once(dirname(__FILE__) . '/../env/'.$env.'/config.php');
$config=dirname(__FILE__).'/../env/'.$env.'/main.php';


//程序根目录
define('CONFIG_SET_BASE_PATH', dirname(__FILE__) . '/protected');

//宏变量定义目录
require_once(dirname(__FILE__).'/protected/config/MacroVars.php');




// specify how many levels of call stack should be shown in each log message
//defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);

Yii::createWebApplication($config)->run();






