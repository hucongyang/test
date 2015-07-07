<?php
class ApplistController extends Controller
{
    public function filters()
    {
        return array(
            array('application.filters.TokenCheckFilter')
        );
    }

    public function actionIndex()
    {
        echo $this->getData(1);
    }

    public function actionFastUp()
    {
        echo $this->getData(2);
    }

    public function actionMostComment()
    {
        echo $this->getData(3);
    }

    public function actionMostUp()
    {
        echo $this->getData(4);
    }
    //app详情
    public function actionAppDetail()
    {
        $appID = Yii::app()->getRequest()->getQuery('appid');
        if (empty($appID)) {
            echo new ReturnInfo(RET_ERROR, 'Argument appid passed to ' . __CLASS__ . '::' . __FUNCTION__ . '() that can not be empty.');
            Yii::app()->end();
        }
        $appInfoObj = AppInfoList::model()->findByPk($appID);
        if (empty($appInfoObj)) {
            echo new ReturnInfo(RET_ERROR, 'Argument appid passed to ' . __CLASS__ . '::' . __FUNCTION__ . '() that can not find a record.');
            Yii::app()->end();
        }

        $author = $appInfoObj->link_user;

        $appInfoArray = array(
            'id' => $appInfoObj->Id,
            'appName' => $appInfoObj->AppName,
            'remarks' => $appInfoObj->Remarks,
            'screenShot' => explode(',', $appInfoObj->IconUrl),
            'commentCount' => $appInfoObj->reply_count,
            'iconUrl' => $appInfoObj->IconUrl,
            'appSource' => $appInfoObj->SourceId,
            'appUrl' => $appInfoObj->AppUrl,
            'up' => $appInfoObj->Up,
        );
        if (!empty($author)) {
            $appInfoArray['author'] = $author->UserName;
            $appInfoArray['authorIcon'] = empty($author['Icon']) ? '' : $author->Icon;
        } else {
            $appInfoArray['author'] = '';
            $appInfoArray['authorIcon'] = '';
        }

        echo new ReturnInfo(RET_SUC, $appInfoArray);
    }
    //获取该APP的评论
    public function actionGetAppComment()
    {
        $appID = Yii::app()->getRequest()->getQuery('appid');
        if (empty($appID)) {
            echo new ReturnInfo(RET_ERROR, 'Argument appid passed to ' . __CLASS__ . '::' . __FUNCTION__ . '() that can not be empty.');
            Yii::app()->end();
        }
        $app = AppInfoList::model()->findByPk($appID);
        if (empty($app)) {
            echo new ReturnInfo(RET_ERROR, 'Argument appid passed to ' . __CLASS__ . '::' . __FUNCTION__ . '() that can not find a record.');
            Yii::app()->end();
        }
        $replies = AppReviews::model()->with('author_icon')->together()->findAll(
            array(
                'select'    => array('Id', 'Pid', 'Content', 'UpdateTime', 'AuthorName'),
                'order'     => 't.Id desc',
                'condition' => 'AppId = :AppId',
                'params'    => array(':AppId' => $appID)
            )
        );
        $appReply = array();
        if (!empty($replies)) {
            foreach ($replies as $reply) {
                if (!isset($appReply[$reply->Id])) {
                    $appReply[$reply->Id] = array(
                        'Id' => $reply->Id,
                        'Content' => $reply->Content,
                        'Pid' => $reply->Pid,
                        'AuthorName' => $reply->AuthorName,
                        'UpdateTime' => $reply->UpdateTime,
                        'AuthorIcon' => $reply->author_icon->Icon,
                        'children' => array()
                    );
                }
                if ($reply->Pid == 0) {
                    continue;
                } else {
                    if (isset($appReply[$reply->Pid])) {
                        $appReply[$reply->Pid]['children'][] = $appReply[$reply->Id];
                        unset($appReply[$reply->Id]);
                    } else {
                        $parentReply = AppReviews::model()->findByPk($reply->Pid);
                        $appReply[$parentReply->Id] = array(
                            'Id' => $parentReply->Id,
                            'Content' => $parentReply->Content,
                            'Pid' => $parentReply->Pid,
                            'AuthorName' => $parentReply->AuthorName,
                            'UpdateTime' => $parentReply->UpdateTime,
                            'AuthorIcon' => $parentReply->author_icon->Icon,
                            'children' => array()
                        );
                        $appReply[$parentReply->Id]['children'][] = $appReply[$reply->Id];
                        unset($appReply[$reply->Id]);
                    }
                }
            }//foreach
        }
        echo new ReturnInfo(RET_SUC, $appReply);
    }

    public function actionGetMaxId() {
        $maxId = AppInfoList::getMaxId();
        echo new ReturnInfo(RET_SUC, $maxId);
    }

    public function getData($order)
    {
        $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        if (!is_numeric($offset) || !is_numeric($limit)) {
            return new ReturnInfo(RET_ERROR, 'offset or limit parameter error');
        }
        $offset = (int) $offset;
        $limit = (int) $limit;
        if ($offset < 0 || $limit < 0) {
            return new ReturnInfo(RET_ERROR, 'offset or limit parameter error');
        }

        $maxId = isset($_GET['maxid']) ? $_GET['maxid'] : 0;
        if (!is_numeric($maxId)) {
            return new ReturnInfo(RET_ERROR, 'maxid parameter error');
        }
        $maxId = (int) $maxId;
        $type = isset($_GET['type']) ? $_GET['type'] : 0;
        $type = CommonFunc::checkIntParam($type, 2, '');
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $category = CommonFunc::checkIntParam($category, Category::getMaxCategory(), '');
        $appsInfo = AppInfoList::getData($order, $type, $search, $category, $offset * $limit, $limit, $maxId);
        return new ReturnInfo(
            RET_SUC,
            array(
                'offset' => $offset,
                'data' => $appsInfo['data']
            )
        );
    }
}
