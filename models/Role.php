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
use yii\web\HttpException;

class Role extends ActiveRecord
{

    
    
    public static function tableName()
    {
        return 'roles_table';
    }

    public function rules(){
        return [ 
            ['r_id', 'required'],
            [['r_id','r_role'],'integer'],
           
        ];
    }

    public function attributeLabels()
    {
        return [
            'r_id' => 'r_id',
            'r_role' => 'r_role',
        ];
    }

    public function returnroleid($id)
    {
        $sql = "SELECT r_role
                FROM roles_table
                WHERE r_id = $id";

        $role = Yii::$app->db->createCommand($sql)->queryOne();
        if($role){
            return $role['r_role'];
            
        }
        else {
            $msg =  "something went wrong";
            return $msg;
            
        }
    }
}