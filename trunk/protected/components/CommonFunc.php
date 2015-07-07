<?php
Class CommonFunc {
    static function encodeURIComponent($str) {
        $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
        return strtr(rawurlencode($str), $revert);
    }

    static function getProjectEnv() {
        $env = 'dev';
        if (is_file(Yii::app()->basePath . '/../../../online')){
            $env = 'online';
        }else if(is_file(Yii::app()->basePath . '/../../../sandbox')){
            $env = 'sandbox';
        }
        return $env;
    }

    /**
     * 设置redis缓存
     * @param $key
     * @param $type
     * @param $value
     * @return bool
     */
    static function setRedis($key, $type, $value) {
        $info = Yii::app()->cache->get($key);           // cache 配置 redis 缓存
        if(!$info) {
            $aInfo = array();
        }else{
            $aInfo = unserialize($info);                // serialize() 序列化，返回字符串，此字符串包含了value的字节流，可以存储于任何地方
        }                                               // unserialize() 反序列化,对单一的已序列化的变量进行操作，将其转换回php的值
        if($type) {
            $aInfo[$type] = $value;
        } else {
            $aInfo = $value;
        }
        Yii::app()->cache->set($key, serialize($aInfo));
        return true;
    }


    /**
     * @param $key
     * @param string $type
     * @return array|mixed
     */
    static function getRedis($key, $type = '') {
        $info = Yii::app()->cache->get($key);
        if(!$info){
            return array();
        }
        $aInfo = unserialize($info);
        return $type ? (isset($aInfo[$type]) ? $aInfo[$type] : array()) : $aInfo;
//        if($type) {
//            if(isset($aInfo[$type])) {
//                return $aInfo[$type];
//            }else {
//                return array();
//            }
//        }else {
//            return $aInfo;
//        }
    }
    
    
    /**
     * 检测微信用户是否在这个广告主的账户下
     * @param $openID 用户的微信原始ID
     * @return void
     */
    static function checkUser($openID, $aUser = array())
    {
        if(empty($openID)){
            $openID = $aUser['openid'];
            $_aUser  = WeixinApi::getUserInfo($openID);
            $aUser['subscribe'] = $_aUser['subscribe'];
        }else{
            $aUser  = WeixinApi::getUserInfo($openID);
        }
        //用 unionid取数据，和微信登录统一用户
        $unionID = $aUser['unionid'];
    
        $user = User::model()->findByAttributes(array('unionid' => $unionID));
        if($user){
            if($aUser['nickname'] !== '' && $user->NickName == $user->UserName && !User::checkUserName($aUser['nickname'])){
                $user->UserName = $aUser['nickname'];
    
            }
            $user->NickName = $aUser['nickname'];
            $user->Icon = $aUser['headimgurl'];
            $user->IsFollow = $aUser['subscribe'];
            //微信登录的用户，这个字段为空
            $user->Openid = $openID;
    
            if($user->save()) {
                //更新redis
                CommonFunc::setRedis('user_'.$user->ID, 'userHeadUrl', $aUser['headimgurl']);
                CommonFunc::setRedis('user_'.$user->ID, 'userName', $user->UserName);
                return $user->ID;
    
            }else{
                Yii::log(__FILE__ . __LINE__ . 'insert fans error', 'error', 'system.api.weixin');
            }
    
        }else{
    
            $model_user = new User();
            if($aUser['nickname'] === '') {
                $aUser['nickname'] = 'name_' . time();
            }
            $model_user->Account = $aUser['nickname'];
            $model_user->NickName = $aUser['nickname'];
            $model_user->UserName = $aUser['nickname'];
            if(User::checkUserName($aUser['nickname'])){
                $model_user->UserName = $aUser['nickname'].'_'.time();
            }
            $model_user->Openid = $openID;
            $model_user->Icon = $aUser['headimgurl'];
            $model_user->unionid = $aUser['unionid'];
            $model_user->CreateTime = date('Y-m-d H:i:s');
            $model_user->Status = 0;
            $model_user->LastLoginTime = date('Y-m-d H:i:s');
            $model_user->IsFollow = $aUser['subscribe'];
            
            
            if($model_user->save()){
                CommonFunc::setRedis('user_'.$model_user->ID, 'userHeadUrl', $aUser['headimgurl']);
                CommonFunc::setRedis('user_'.$model_user->ID, 'userName', $model_user->UserName);
    
                return $model_user->ID;
            }else{
                Yii::log(__FILE__ . __LINE__ . 'insert fans error', 'error', 'system.api.weixin');
            }
        }
    
        Yii::app()->end();
    
    }

    //通过匹配用户代理信息判断用户设备类型
    static function isWeiXin(){
        return (stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false);
    }

    static function isMobile(){
        return (stripos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false);
    }

    static function isAndroid(){
        return (stripos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false);
    }

    static function isIOS(){
        return (stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false) || (stripos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false);
    }

    static function transUrl($url){
        if(self::isMobile()){
            if(strpos($url, 'anzhi') !== false) {
                $url = str_replace(array('www', 'soft'), array('m', 'info'), $url);     // str_replace() 子字符串替换
            }
        }
        return $url;
    }

    /**
     * 检查整数类型变量
     * @param $param         传入参数
     * @param $max           参数最大值
     * @param int $default   默认参数值
     * @return int           返回值
     */
    static function checkIntParam($param, $max, $default = 0){
        if(!is_numeric($param)){
            return $default;
        }
        $param = (int) $param;
        if($param < 0 || $param > $max) {
            return $default;
        }
        return $param;
    }
}