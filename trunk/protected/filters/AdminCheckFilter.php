<?php

class AdminCheckFilter extends CFilter
{
    protected function preFilter($filterChain)
    {
        if(Yii::app()->user->isGuest){                 //判断用户是否为游客
            Yii::app()->user->loginRequired();            //游客，提示先登录
        }else{
            $aOpenid = array(
                'ohdH2s23MGn5EaWIVqT9B979dN5c',
                'ohdH2s5ustufKzGUUBvZW1KGQFB4',
                'ohdH2s0pdej67O8EKZ-kfdqqVQMA',
                'ohdH2s0EimJdDekgeu4TN8EExgXE',
                'ohdH2s8zSzWpPbx1tY5BW1MaiLlg'
            );

            $openid = Yii::app()->user->openid;              //得到用户登录的微信唯一openid
            return in_array($openid, $aOpenid);
        }
    }
}