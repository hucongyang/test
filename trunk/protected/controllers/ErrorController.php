<?php

class ErrorController extends Controller
{
    /**
     * Declares class-based actions.
     */
    //public $layout='//layouts/exception';
    public $aErrorMsg = array(
            '1000' => array('title' => '出错啦~', 'message' => 'Opps!!!'),
            '200' => array('title' => '出错啦~', 'message' => ''),
            '404' => array('title' => '404 NOT FOUND', 'message' => '哎呀...您访问的页面不存在'),
            '1101' => array('title' => '', 'message' => ''),
            '1102' => array('title' => '非常抱歉~~~', 'message' => '工程师忙成狗了，客户端还在玩命研发中...'),
            '1103' => array('title' => '出错啦~', 'message' => '请关注微信公众号"appgrub"后,在对话框中点击进入网站完成登录!'),
        );

	public function filters()
	{
	    return array();
	}

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {
            if($error['code'] != 404 || !isset($aErrorMsg[$error['errorCode']])){
                Yii::log(' error : ' . $error['file'] .":". $error['line'] .":". $error['message'], 'error', 'system');
            }
            $ret = new ReturnInfo(FAIL_RET, Yii::t('exceptions', $error['message']), intval($error['errorCode']));
            if(Yii::app()->request->getIsAjaxRequest()){
                echo json_encode($ret);
                
            }else{
                if( empty($error['errorCode']) ){
                    if(isset($this->aErrorMsg[$error['code']])){
                        if(empty($this->aErrorMsg[$error['code']]['message'])) {
                            $this->aErrorMsg[$error['code']]['message'] = $error['message'];
                        }
                        $this->render('error', $this->aErrorMsg[$error['code']]);
                    }else{
                        $this->render('error', $this->aErrorMsg['1000']);
                    }
                }else{
                    $this->render('error', $this->aErrorMsg[ $error['errorCode'] ]);
                    
                }
            }
            
        } 
    }

    /**
     * Displays the contact page 
     */
    public function actionContact()
    {
        $model=new ContactForm;
        if(isset($_POST['ContactForm']))
        {
            $model->attributes=$_POST['ContactForm'];
            if($model->validate())
            {
                $headers="From: {$model->email}\r\nReply-To: {$model->email}";
                mail(Yii::app()->params['adminEmail'],$model->subject,$model->body,$headers);
                Yii::app()->user->setFlash('contact','Thank you for contacting us. We will respond to you as soon as possible.');
                $this->refresh();
            }
        }
        $this->render('contact',array('model'=>$model));
    }

    /**
     * Displays the login page
     */
    public function actionLogin()
    {
        $model=new LoginForm;

        // if it is ajax validation request
        if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
        {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if(isset($_POST['LoginForm']))
        {
            $model->attributes=$_POST['LoginForm'];
            // validate user input and redirect to the previous page if valid

            if($model->validate() && $model->login())
                $this->redirect(Yii::app()->user->returnUrl);
        }
        // display the login form
        $this->render('login',array('model'=>$model));
    }

    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }
}