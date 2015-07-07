<?php

/*
 * 主页控制器
 */

class AppController extends Controller{

    public function actionIndex()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];                // $SERVER 是包含诸如头信息，路径，脚本位置等信息的数组  HTTP_USER_AGENT 该字符串表明访问该页面的用户代理的信息(浏览器信息)
        $categoryModel = new Category();
        $systemCategory = $categoryModel->getCategory();
        //  var_dump($systemCategory);exit;
        //  $ua 取得浏览器信息，stripos() 匹配字符串信息，此处匹配成功则为移动设备访问
        if(stripos($ua, 'Mobile') > 0){                   //stripos() 查找字符串首次出现的位置(不区分大小写); strpos() 功能相同，区分大小写
            $isFollow = 0;
            $userId = Yii::app()->user->id;
            if($userId) {
                $aUser = User::model()->findByPk($userId);
                if($aUser){
                    $isFollow = $aUser->IsFollow;           // user IsFollow 用户表是否粉丝标志位
                }
            }
            $this->render('app', array(
                'userId' => $userId,
                'isFollow' => $isFollow,
                'systemCategory' => $systemCategory,
                ));
            return;
        }
        $order = isset($_GET['order']) ? $_GET['order'] : 1;
        $order = CommonFunc::checkIntParam($order, 4, '');
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $type = CommonFunc::checkIntParam($type, 2, '');
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $category = CommonFunc::checkIntParam($category, Category::getMaxCategory(), '');
        if(!isset($systemCategory[$category])) {
            $category = 0;
        }
        $maxId = AppInfoList::getMaxId();
        $appsInfo = AppInfoList::getData($order, $type, $search, $category);
        $this->render(
            'app',
            array(
                'data' => $appsInfo['data'],
                'pagecount' => $appsInfo['pageCount'],
                'maxid' => $maxId,
                'order' => $order,
                'category' => $category,
                'type'  => $type,
                'search'  => $search,
                'systemCategory' => $systemCategory,
            )
        );
    }

    public function actionList()
    {
        $order = isset($_POST['order']) ? $_POST['order'] : 1;
        $order = CommonFunc::checkIntParam($order, 4, 1);
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $type = CommonFunc::checkIntParam($type, 2, '');
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $category = CommonFunc::checkIntParam($category, Category::getMaxCategory(), '');
        $maxId = isset($_POST['maxid']) ? $_POST['maxid'] : 0;
        $page = isset($_POST['page']) ? $_POST['page'] : 0;
        if(!$maxId){
            $maxId = AppInfoList::getMaxId();
        }
        $appsInfo = AppInfoList::getData($order, $type, $search, $category, $_POST['page'] * 25, 25, $maxId);
        echo new ReturnInfo(RET_SUC, array('list'=>$appsInfo['data'], 'maxid'=>$maxId, 'pageCount' => $appsInfo['pageCount']));
    }
}