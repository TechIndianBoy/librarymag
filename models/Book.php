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

class Book extends ActiveRecord
{
    public static function tableName()
    {
        return 'books_table';
    }

    public function rules(){
        return [ 
            [['b_title','b_author','b_catagory'],'required'],
            [['b_title','b_author','b_catagory'],'string'],
            ['b_count','integer'],

           
        ];
    }

    public function attributeLabels()
    {
        return [
            'b_id' => 'b_id',
            'b_author' => 'b_author',
            'b_title' => 'b_title',
            'b_catagory' => 'b_catagory',
            'b_description' => 'b_description',
            'b_count' => 'b_count',

        ];
    }

    public function seeallbook()
    {
        $sql = "select * from books_table";
        $rslt = Yii::$app->db->createCommand($sql)->query()->readAll();
        return $rslt;

    }

    public function returnlatestid()
    {
        $sql = "SELECT max(b_id)
                FROM books_table";

        $rslt = Yii::$app->db->createCommand($sql)->query()->read();
        return $rslt['max'];exit;

    }

    public function returnbookid($bookname)
    {
        $sql = "SELECT b_id
                FROM books_table
                WHERE b_title = '$bookname'";
        
        $b_id = Yii::$app->db->createCommand($sql)->query()->read();
        
        if($b_id){
            return $b_id['b_id'];
        }
        else{
            throw new HttpException(401, "invalid book name");
        }
        
    }

    public function updatebookcount($book_id,$count)
    {
        $sql = "UPDATE books_table
                SET b_count =  b_count + $count
                WHERE b_id = $book_id";
        $rslt = Yii::$app->db->createCommand($sql)->execute();
        if($rslt){
            return 1;
        }else{
            return 0;
        }

    }
        

}
?>
