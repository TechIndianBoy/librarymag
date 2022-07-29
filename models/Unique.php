<?php

namespace app\models;

use Firebase\JWT\JWT;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\rbac\Permission;
use yii\web\Request as WebRequest;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class Unique extends ActiveRecord
{
    private $uni_title;
    private $uni_description;
    private $uni_catagory;
    public $cata;
    private $r_id;
    private $r_role;
    public static function tableName()
    {
        return 'yiitest';
    }

    public function database()
    {
        return 'yii2basic';
    }

    public function rules(){
        return [
           
        ];
    }


    public function attributeLabels()
    {
        return [
           'r_id' => 'para_id',
           'para_name' => 'para_name',
           'poiuytrew' => 'poiuytrewq'
        ];
    }

}


?>