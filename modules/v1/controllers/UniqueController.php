<?php
namespace app\modules\v1\controllers;

use app\filters\auth\HttpBearerAuth;

use app\models\Unique;
use GuzzleHttp\Psr7\Query;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\helpers\Url;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class UniqueController extends ActiveController
{
    
   public $modelClass = 'app\models\Unique';

    public function actions()
    {
        return [];
    }

    

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className(),
            ],

        ];

        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::className(),
            'actions' => [
                'hello' => ['get'],
                'interact' => ['get'],
                // 'view' => ['get'],
                // 'create' => ['post'],
                // 'update' => ['put'],
                // 'delete' => ['delete'],
                // 'login' => ['post'],
                'me' => ['get', 'post'],
            ],
        ];

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
            ],
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = [
            // 'options',
            // 'login',
            // 'signup',
            // 'confirm',
            // 'password-reset-request',
            // 'password-reset-token-verification',
            // 'password-reset',
            'interact',
            'hello',
        ];

        // setup access
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => ['index','create','view', 'update', 'delete'], //only be applied to
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['interact','hello'],
                    'roles' => ['admin', 'manageUsers'],
                ],
                [
                    'allow' => true,
                    'actions' => ['me'],
                    'roles' => ['user']
                ]
            ],
        ];

        return $behaviors;
    }

    public function actionHello()
    {
        $sql = "select * from users_table";
        
        $model = Yii::$app->db->createCommand($sql)->queryAll();
        // print_r($model[0]);
        foreach($model[0] as $ky => $vl){
            echo "<br>$ky => $vl<br>"; 
        }
        
    }
        
       
    

    public function actionInteract()
    {
        // echo "interact working";
        $sql = "delete from users_table where u_id = 2100";
        $model = Yii::$app->db->createCommand($sql)->queryall();


    }

    public function actionErace()
    {
        $sql ="";
        $model = Yii::$app->db->createCommand($sql)->queryall();
    }


}

?>