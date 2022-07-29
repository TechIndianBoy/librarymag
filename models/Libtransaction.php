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
use DateTime;

class Libtransaction extends ActiveRecord
{

    
    
    public static function tableName()
    {
        return 'transactions_table';
    }

    public function rules(){
        return [ 
            [['user_id','book_id','copy_id','issued_date'],'required'],
            [['user_id','book_id','copy_id','t_fine','t_status'],'integer'],
            [['issued_date','exp_returndate','act_returneddate','t_createddate','t_updateddate'],'safe'],
            
           
        ];
    }

    public function attributeLabels()
    {
        return [
            't_id'             => 't_id',
            'user_id'          => 'user_id',
            'book_id'          => 'book_id',
            'copy_id'          => 'copy_id',
            'issued_date'      => 'issued_date',
            'exp_returndate'   => 'exp_returndate',
            'act_returneddate' => 'act_returneddate',
            't_fine'           => 't_fine',
            't_status'         => 't_status',
            't_createddate'    => 't_createddate',
            't_updateddate'    => 't_updateddate',
        ];
    }

    public function cantransact($user_id,$book_id)
    {
        $cantransact = 1;
        $sql = "SELECT * 
                FROM transactions_table
                WHERE user_id = $user_id AND book_id = $book_id AND t_status = 1";
        $status =  Yii::$app->db->createCommand($sql)->queryAll();
        if($status){
            $cantransact = 0;
        }
        return $cantransact;


    }

    public function returntransid($user_id,$book_id)
    {
        $sql = "SELECT t_id
                FROM transactions_table
                WHERE user_id = $user_id AND book_id = $book_id AND t_status = 1";
        $rslt = Yii::$app->db->createCommand($sql)->query()->read();

        if ($rslt){
            $t_id = $rslt['t_id'];
            return $t_id;
        }else{
            throw new HttpException(409,"no such data found(gettransid)");
        }


    }

    public function calculatefine($trans_id)
    {
        $fine = 0;
        $sql = "SELECT act_returneddate,
                       exp_returndate
                FROM transactions_table
                WHERE t_id = $trans_id";
        $rslt = Yii::$app->db->createCommand($sql)->query()->read();

        $actdate = new DateTime($rslt['act_returneddate']);
        $expdate = new DateTime($rslt['exp_returndate']);
        $fineondays = $actdate -> diff($expdate);

        if($fineondays->days > 0){
            $fine = $fineondays->days * 5;
        }
        return $fine;

    }

    public function returnbook($trans_id,$returndate)
    {
        $msg = "program did not go inside";

        $sql = "UPDATE transactions_table
                SET act_returneddate = '$returndate',
                    t_status = 0
                WHERE t_id =$trans_id";
        $rslt = Yii::$app->db->createCommand($sql)->execute();

        if($rslt){
            $msg = 1;
            
        }else{
            $msg = "some error caugth while returning";
            
        }
        return $msg;
    }

    public function addfineintable($trans_id,$fine)
    {
        $msg = "No fine";
        $sql = "UPDATE transactions_table
                SET t_fine = $fine,
                    t_updateddate = now()
                WHERE t_id = $trans_id";
        $rslt = Yii::$app->db->createcommand($sql)->execute();
        if($rslt){
            return 1;
        }
        return $msg;
    }

    public function returncopyid($trans_id)
    {
        $sql = "SELECT copy_id
                FROM transactions_table
                WHERE t_id = $trans_id";
        $rslt = Yii::$app->db->createCommand($sql)->query()->read();
        if($rslt){
            return $rslt['copy_id'];
        }else{
            return "error";
        }
    }
}