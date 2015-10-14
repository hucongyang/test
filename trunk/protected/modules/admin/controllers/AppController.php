<?php

class AppController extends Controller {

    /**
     * 过滤器定义访问规则
     * @return array
     */
    public function filters()
   {
       return array(
           array(
               'application.filters.AdminCheckFilter',
           )
       );
   }

    /**
     * 直接渲染页面,详细内容通过jquery的ajax加载
     *App 列表页面
     */
    public function actionShow() {
        $this->render('show');
    }

    /**
     *show 页面加载后，ajax发请求到list返回$list
     */
    public function actionList() {
        $os = Yii::app()->request->getParam('os', '');                      // isset($_POST['os']) ? isset($_POST['os']) : $default
        $order = Yii::app()->request->getParam('order', 'DownLoadNum');     //Yii::app()->request->getParam($name, $default)  post或get 取得前台提交的值
        $where = 'b.Status = 0';
        if($os) {
            $where .= ' AND c.OS = "' . addslashes($os) . '" ';           //addslashes() 返回字符串，该字符串为了数据库查询语句等的需要在某些字符钱加上了反斜线
        }                                                                    // 单引号内部的变量不会执行，双引号内部变量会执行
        $list = Yii::app()->db->createCommand()                             // ifnull(name, default)  name为属性值, default为name不存在时的默认值
            ->select('a.PushId, b.IconUrl, b.AppName, b.PusherId, d.Name as MainCategory, ifnull(b.FileSize, "") as FileSize, c.OS, b.ProcessDate, max(a.CommentNum) as CommentNum, max(a.DownLoadNum) as DownLoadNum')
            ->from('app_push_list_detail a')
            ->join('app_push_list b', 'a.PushId = b.Id')
            ->join('source c', 'b.SourceId = c.ID')
            ->join('category d', 'b.MainCategory = d.ID')
            ->group('a.PushId')
            ->where($where)
            ->order($order . ' desc')
            ->queryAll();

        $models = Source::model()->findAll();
        $aPusher = array();
        foreach($models as $m){
            $aPusher[ $m->ID ] = $m->ChnName;
        }
        $aPusher[ 0 ] = '';

        $new_list = array();
        foreach($list as $row){
            $row['ChnName'] = $aPusher[ intval($row['PusherId']) ];
            $new_list[] = $row;
        }
        echo new ReturnInfo(0, $new_list);
    }

    /**
     *App详情页面
     */
    public function actionDetail()
    {
        $id = Yii::app()->request->getParam('id');
        if(!isset($id) || empty($id)){
            throw new THttpException('查看的App不存在');
        }
        if(!is_int($id)){
            $id = intval($id);
        }
        $link = AppPushList::model()->findByPk($id);
        if(!($link instanceof AppPushList)){
            throw new THttpException('查看的App信息不存在');
        }
        $aInfo = array();
        //app_push_list表的基本信息
        $aInfo['Id'] = $link->Id;
        $aInfo['AppName'] = $link->AppName;
        $aInfo['IconUrl'] = $link->IconUrl;
        $aInfo['SourceId'] = $link->SourceId;
        $aInfo['AppUrl'] = $link->AppUrl;
        $aInfo['AppInfo'] = $link->AppInfo;
        $aInfo['ProcessDate'] = $link->ProcessDate;
        $aInfo['FileSize'] = $link->FileSize;
        //App市场名称
        $aInfo['ChnName'] = Source::model()->findByPk($aInfo['SourceId'])->ChnName;
        if(empty($aInfo['ChnName'])){
            $aInfo['ChnName'] = '';
        }
        //App所属类型
        $aInfo['CategoryName'] = Category::model()->findByPk($link->MainCategory)->Name;
        if(empty($aInfo['CategoryName'])){
            $aInfo['CategoryName'] = '';
        }

        //信息的轮播图片（link_info表）
        $aInfo['imgurl'] = array();
        if(!empty($link->ScreenShoot)){
            foreach(explode(',', $link->ScreenShoot) as $imageUrl){
                $aImage = array();
                $aImage['imgurl'] = $imageUrl;
                $aInfo['imgurl'][] = $aImage;
            }
        }

        //App的下载总量/评论总量
        $modal_detail = AppPushListDetail::model()->findAll(
            array(
                'select' => array('Id', 'AppId', 'ApkName', 'MAX(DownLoadNum) as DownLoadNum', 'MAX(CommentNum) as CommentNum', 'PushId'),
                'condition' => 'PushId = :Id',
                'params' => array(':Id' => $id)
            )
        );
        foreach($modal_detail as $num){
            $aInfo['DownLoadNum'] = $num->DownLoadNum;
            $aInfo['CommentNum'] = $num->CommentNum;
        }

        //App评论内容
        $aReply = array();
        $modal_reply = AppPushListReviews::model()->findAll(
            array(
                'select' => array('Id', 'PushId', 'Title', 'Content', 'UpdateTime', 'Status'),
                'condition' => 'PushId = :Id',
                'order' => 'UpdateTime desc',
                'params' => array(':Id' => $id)
            )
        );
        if(!empty($modal_reply)){
            foreach($modal_reply as $reply){
                $aReply[] = array(
                    'Id' => $reply->Id,
                    'PushId' => $reply->PushId,
                    'Title' => $reply->Title,
                    'Content' => $reply->Content,
                    'UpdateTime' => $reply->UpdateTime,
                    'Status' => $reply->Status
                );
            }
        }

        //App 下载量/评论量 分析
        $models_date  = AppPushListDetail::model()->findAll(
            array(
                'select' => array('PushId', 'DownLoadNum', 'CommentNum', 'Date'),
                'condition' => 'PushId = :Id',
                'order' => 'Date',
                'params' => array(':Id' => $id)
            )
        );
        $num = array();
        foreach($models_date as $row) {
            $num['Date'][] = $row->Date;
            $num['DownLoadNum'][] = intval($row->DownLoadNum);
            $num['CommentNum'][] = intval($row->CommentNum);
        }
        if(!empty($num['Date'])){
            $Date_json = json_encode($num['Date']);
        }else{
            $Date_json = 0;
        }
        if(!empty($num['DownLoadNum'])){
            $DownLoadNum_json = json_encode($num['DownLoadNum']);
        }else{
            $DownLoadNum_json = 0;
        }
        if(!empty($num['CommentNum'])){
            $CommentNum_json = json_encode($num['CommentNum']);
        }else{
            $CommentNum_json = 0;
        }

        $this->render('detail', array('data' => $aInfo,
            'Date_json' => $Date_json,
            'DownLoadNum_json' => $DownLoadNum_json,
            'CommentNum_json' => $CommentNum_json,
            'aReply' => $aReply));
    }

    /**
     *删除App操作
     * @throws THttpException
     */
    public function actionDelete()
    {
        if(!isset($_POST['id']) || empty($_POST['id'])) {           // isset() 变量是否设置, empty() 变量是否为空
            throw new THttpException('勾选项不能为空');
        }
        $id = $_POST['id'];                                           // $_POST['id'] 接收前台参数id
        if(!is_array($id)) {                                          // is_array() 判断是否为数组
            $id = array($id);
        }
        try{
            foreach($id as $row) {
                $modal_app = AppPushList::model()->findByPk($row);
                if(!($modal_app instanceof AppPushList)) {               //instanceof 判断是否为某个对象
                    throw new THttpException('操作失败');
                }
                $modal_app->Status = '-1';
                if(!$modal_app->save()) {                                   // save() 保存所做改动
                    throw new Exception();
                }
                AppPushListDetail::model()->deleteAll('PushId=' . $row);     //根据condition条件执行查询删除操作
                AppPushListReviews::model()->deleteAll('PushId=' . $row);
            }
            echo new ReturnInfo(RET_SUC, '删除成功');
        }catch (Exception $e) {
            throw new THttpException('操作失败');
        }
    }


    /**
     *添加App操作
     * input: array $id
     * @throws THttpException
     */
    public function actionAdd()
    {
        if(!isset($_POST['id']) || empty($_POST['id'])) {
            throw new THttpException('操作失败');
        }
        $id = $_POST['id'];
        if(!is_array($id)) {
            $id = array($id);
        }
        try{
            foreach($id as $row) {
                $modal_list = AppPushList::model()->findByPk($row);
                if(!($modal_list instanceof AppPushList)) {
                    throw new THttpException('操作失败');
                }
                $modal_filter = new AppHasFiltered();

                $modal_list->Status = '1';
                $modal_filter->PushId = $modal_list->Id;
                $modal_filter->AppId = $modal_list->AppId;
                $modal_filter->SourceId = $modal_list->SourceId;
                $modal_filter->AppName = $modal_list->AppName;
                $modal_filter->MainCategory = $modal_list->MainCategory;
                $modal_filter->IconUrl = $modal_list->IconUrl;
                $modal_filter->AppUrl = $modal_list->AppUrl;
                $modal_filter->ScreenShoot = $modal_list->ScreenShoot;
                $modal_filter->VideoUrl = $modal_list->VideoUrl;
                $modal_filter->MoveTime = new CDbExpression('NOW()');           //添加当前时间
                $modal_filter->OfficialWeb = $modal_list->OfficialWeb;
                $modal_filter->Status = '1';
                $modal_filter->AppInfo = $modal_list->AppInfo;
                $modal_filter->ApkUrl = $modal_list->ApkName;
                $transaction = Yii::app()->db->beginTransaction();
                try{
                    if(!$modal_list->save() || !$modal_filter->save()) {
                        throw new Exception();
                    }
                    $transaction->commit();
                }catch (Exception $e) {
                    $transaction->rollback();
                }
            }
            echo new ReturnInfo(RET_SUC, '添加成功');
        }catch (Exception $e) {
            throw new THttpException('操作失败');
        }
    }

    public function actionEditComment(){
        if(!isset($_POST['id']) || empty($_POST['id'])){
            throw new THttpException('评论ID为空！');
        }
        if(!isset($_POST['content']) || $_POST['content'] === ''){
            throw new THttpException('评论内容为空！hhh');
        }

        $comment = AppPushListReviews::model()->findByPk($_POST['id']);
        if(!$comment) {
            throw new THttpException('评论不存在！');
        }
        $comment->Content = $_POST['content'];
        if(!$comment->save()){
            throw new THttpException('编辑评论失败！');
        }
        echo new ReturnInfo(0, '保存成功！');
        return;
    }

    public function actionDeleteComment(){
        if(!isset($_POST['id']) || empty($_POST['id'])){
            throw new THttpException('评论ID为空！');
        }
        $id = $_POST['id'];
        if (!is_array($id)) {
            $id = array($id);
        }
        try{
            $criteria =new CDbCriteria; 
            $criteria->addInCondition('Id', $id); 
            AppPushListReviews::model()->deleteAll($criteria);
            echo new ReturnInfo(RET_SUC, '删除评论成功');
        }
        catch (Exception $e) {
            throw new THttpException('操作失败');
        }
    }

    public function actionEditAppInfo(){
        if(!isset($_POST['app_id']) || empty($_POST['app_id'])) {
            throw new THttpException('应用介绍ID为空！');
        }
        if(!isset($_POST['content']) || $_POST['content'] === ''){
            throw new THttpException('应用介绍内容为空！');
        }

        $app_info = AppPushList::model()->findByPk($_POST['app_id']);
        if(!$app_info) {
            throw new THttpException('应用介绍不存在！');
        }
        $app_info->AppInfo = $_POST['content'];
        if(!$app_info->save()){
            throw new THttpException('编辑应用介绍失败！');
        }
        echo new ReturnInfo(0, '保存成功！');
        return;
    }
}