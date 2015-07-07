<?php
class Source extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'source';
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array();
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Logmsg the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获得source表Domains值存入数组中,
     * 某些字段有多个网址，用explode()把字段转化为数组
     * @return array
     */
    static function getSourceDomains()
    {
        $sourceArray = array();                                                             // array array_merge() 合并数组
        foreach (self::model()->findAll() as $source) {                                   // explode() 拆分字符串
            $sourceArray = array_merge($sourceArray, explode(',', $source->Domains));
        }
        return $sourceArray;
    }

    static function getSourceName($sourceID)
    {
        $sourceObj = self::model()->findByPk($sourceID);
        if ($sourceObj instanceof Source) {
            return $sourceObj->ChnName;
        }
    }

    static function getSourceOS($sourceID)
    {
        $sourceObj = self::model()->findByPk($sourceID);
        if ($sourceObj instanceof Source) {
            return $sourceObj->OS;
        }
    }

    static function getSourceByDomain($Domain)
    {
        $appHost = parse_url($Domain);
        if (isset($appHost['host'])) {
            $criteria = new CDbCriteria;
            $criteria->addSearchCondition('Domains', $appHost['host']);
            $source = Source::model()->find($criteria);
            if($source){
                return $source->ID;
            }
        } else {
            echo new ReturnInfo(RET_SUC, array('code' => -1, 'msg' => 'App链接有误,请参考填写规则'));
            Yii::app()->end();
        }
    }
}
