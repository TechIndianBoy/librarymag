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

class Usertable extends ActiveRecord
{

    
    
    public static function tableName()
    {
        return 'users_table';
    }

    public function rules(){
        return [ 
            [['u_name','u_username','u_password','u_email','u_phno','u_bookissued','u_updateddate','u_dob','access_token','access_token_exp_date'],'required'],
            [['u_name','u_username','u_password'],'string'],
            [['u_phno','u_bookissued'],'integer'],
            [['u_updateddate','u_dob','access_token_exp_date'],'safe'],
            [['u_email'],'email'],
            [['u_username','u_email','u_phno'],'unique'],
           
        ];
    }

    public function attributeLabels()
    {
        return [
            'u_id' => 'u_id',
            'u_name' => 'u_name',
            'u_username' => 'u_username',
            'u_password' => 'u_password',
            'u_email' => 'u_email',
            'u_phno' => 'u_phno',
            'u_status'=>'u_status',
            'u_bookissued' => 'u_bookissued',
            'u_updateddate' => 'u_updateddate',
            'u_createddate' => 'u_updateddate',
            'access_token_exp_date' => 'access_token_exp_date',
            'access_token' => 'access_token',
            'u_dob' => 'u_dob',
        ];
    }

    public function validatetoken()
    {
        
        $header = Yii::$app->request;
        $token = $header->headers->get('authorization');
        if (!empty($token)) {
        (preg_match('/^(?i)Bearer (.*)(?-i)/', $token, $matches));
        return $matches[1];
            
        }
        else{
            throw new HttpException(422,"invalid token");
        }

    }

    public function validateuser($username)
    {
        
        $userrqst = new Usertable();
        $tokenrqst = $userrqst->validatetoken();
        
        $var = $userrqst->find()->where(['access_token'=>$tokenrqst])->andWhere(['u_username'=>$username])->one();
    
       
        if (!empty($var)){
            return 1;
        }else{
            throw new HttpException(401,"Unauthorized user access in validateuser");
        }
    }

    public function seealluser()
    {
        $sql = 'SELECT u_id, u_name, u_username, u_email, u_phno, u_bookissued, u_dob, u_status
                FROM users_table';

        $rslt = Yii::$app->db->createCommand($sql)->queryAll();
        return $rslt;

    }

    public function updatepassword($username, $pwd)
    {
        if(!empty($username) && !empty($pwd)){

        $pwdhash = Yii::$app->getSecurity()->generatePasswordHash($pwd);

        $sql = "UPDATE users_table
                SET u_password = '$pwdhash',
                    u_updateddate = now()
                WHERE u_username = '$username'";

        $updationstatus = Yii::$app->db->createCommand($sql)->execute();

        if($updationstatus == 1){
            $msg = "updation done";
        }
        else{
            $msg = "username does not exist.";
        }

        return $msg;

        }
        else{
            throw new HttpException(422,"username and updated password cannot be empty");
        }

    }

    public function returnuserid($username)
    {
        $sql = "SELECT u_id
                FROM users_table
                WHERE u_username = '$username'";
        
        $u_id = Yii::$app->db->createCommand($sql)->query()->read();
        
        if($u_id){
            return $u_id['u_id'];
        }
        else{
            throw new HttpException(401, "invalid username");
        }
        
    }

    public function senddetailswhenlogin($username)
    {
        $sql = "SELECT u_id,
                       u_name,
                       u_username,
                       u_status,
                       access_token 
                FROM users_table 
                WHERE u_username = '$username'";

        $rslt = Yii::$app->db->createCommand($sql)->query()->read();
        
        
        return $rslt;
    }

    public function varifyusername($username)
    {
        $sql    = "SELECT * 
                   FROM users_table
                   WHERE u_username = '$username'";
        $verify = Yii::$app->db->createCommand($sql)->queryAll();
        if($verify){
            return 0;
        }
        else{
            return 1;
        }
    }

    public function varifyemail($email)
    {
        $sql    = "SELECT * 
                   FROM users_table
                   WHERE u_email = '$email'";
        $verify = Yii::$app->db->createCommand($sql)->queryAll();
        if($verify){
            return 0;
        }
        else{
            return 1;
        }
    }

    public function varifyphonenumber($phno)
    {
        $sql    = "SELECT * 
                   FROM users_table
                   WHERE u_phno = $phno";
        $verify = Yii::$app->db->createCommand($sql)->queryAll();
        if($verify){
            return 0;
        }
        else{
            return 1;
        }
    }

    public function updatebookcount($user_id)
    {
        $sql = "UPDATE users_table
                SET u_bookissued = u_bookissued +1
                WHERE u_id = $user_id";
        $update = Yii::$app->db->createCommand($sql)->execute();
        if($update){
            
            return 1;
        }
        else{
            
            return 0;
        }
    }

    public function getissuedbook($user_id)
    {
        
        $sql = "SELECT u_bookissued
                FROM users_table
                WHERE u_id = $user_id";
        $count = Yii::$app->db->createCommand($sql)->query()->read();
        if($count){
            return $count['u_bookissued'];
        }
    }

    public function addfineintable($user_id,$fine)
    {
        $sql = "UPDATE users_table
                SET u_fine = u_fine + $fine,
                    u_bookissued = u_bookissued -1,
                    u_updateddate = now()
                WHERE u_id = $user_id";
        $rslt = Yii::$app->db->createCommand($sql)->execute();

        if($rslt){
            return 1;
        }else{
            $msg = "error caught adding fine";
            return $msg;
        }
    }

    public function deleteuser($user_id)
    {
        $sql = "UPDATE users_table
                SET u_status = 0,
                    u_updateddate = now()
                WHERE u_id = $user_id AND u_bookissued = 0 AND u_fine = 0";
        $rslt = Yii::$app->db->createCommand($sql)->execute();
        if($rslt){
            return 1;
        }else{
            return 0;
        }
        
    }

    public function checkforfine($user_id)
    {
        $sql = "SELECT u_fine
                FROM users_table
                WHERE u_id = $user_id";
        $rslt = Yii::$app->db->createcommand($sql)->query()->read();
        if($rslt['u_fine'] == 0){
            return 1;
        }else{
            return $rslt['u_fine'];
        }


    }

    

}