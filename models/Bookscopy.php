<?php

namespace app\models;

use Exception;
use Firebase\JWT\JWT;
use Symfony\Contracts\Service\Attribute\Required;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\rbac\Permission;
use yii\web\Request as WebRequest;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\web\HttpException;

class Bookscopy extends ActiveRecord
{
    public static function tableName()
    {
        return 'b_copies_table';
    }

    public function rules(){
        return [ 
            [['b_id','c_ispn'],'required'],
            [['b_id','c_ispn','c_availability'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'c_id' => 'c_id',
            'b_id' => 'b_id',
            'c_ispn' => 'c_ispn',
        ];
    }

    public function addcopies($book_id,$count)
    {

        if (!empty($book_id) && !empty($count)){

            try{            
            for($c = 1;$c <= $count ; $c++){
                $sql =  "INSERT INTO b_copies_table(b_id, c_ispn)
                        VALUES ($book_id, $c)";

                $task = Yii::$app->db->createCommand($sql)->execute();  
            }
            if($task == 1){
                $msg = "ispn created";
                return $msg;
            }else{
                $msg = "something went wrong in copies table";
                return $msg;
            }

            }
            catch(Exception $e){
                throw new HttpException(422, $e->getMessage());
            }
        
        }
        else{
            throw new HttpException(422,'Book id and Number of books can not be empty..!');

        }

    }

    public function seeavailability($book_id)
    {
        $sql  = "SELECT c_id 
                 FROM b_copies_table
                 WHERE c_availability = 1 AND b_id = $book_id";
        
        $availability = Yii::$app->db->createCommand($sql)->queryOne();

        if($availability == false){
            $msg = "No copy available of given Copy";
            return $msg;
        }else{
        return $availability['c_id'];}

    }

    public function makeunavailable($c_id)
    {
        $sql = "UPDATE b_copies_table
                SET c_availability = 0
                WHERE c_id = $c_id";

        $unavailable = Yii::$app->db->createCommand($sql)->execute();
        if($unavailable){
            return 1;
        }else{
            return 0;
        }
    }

    public function makeavailable($c_id)
    {
        $sql = "UPDATE b_copies_table
                SET c_availability = 1
                WHERE c_id = $c_id";

        $unavailable = Yii::$app->db->createCommand($sql)->execute();
        if($unavailable){
            return 1;
        }else{
            return 0;
        }
    }

    public function findmaxcopy($book_id)
    {
        $sql = "SELECT max(c_ispn)
                FROM b_copies_table
                WHERE b_id = $book_id";
        $maxispn = Yii::$app->db->createCommand($sql)->query()->read();

        if($maxispn){
            return $maxispn['max'];
        }else{
            return 0;
        }
    }

    public function addnewcopies($book_id,$newcount,$startcount)
    {
        for ($c = $startcount+1; $c <= $startcount + $newcount; $c++){
            $sql =  "INSERT INTO b_copies_table(b_id, c_ispn)
                     VALUES ($book_id, $c)";

            $task = Yii::$app->db->createCommand($sql)->execute();
        }
        if($task){
            return 1;
        }else{
            return 0;
        }

        
    }




}