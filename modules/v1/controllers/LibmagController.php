<?php
namespace app\modules\v1\controllers;

use app\filters\auth\HttpBearerAuth;

use app\models\User;
use app\models\Usertable;
use app\models\Book;
use app\models\Bookscopy;
use app\models\Role;
use app\models\Libtransaction;


use Error;
use Exception;
use GuzzleHttp\Psr7\Query;
use phpDocumentor\Reflection\Types\Integer;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\helpers\Url;
use yii\rest\ActiveController;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class LibmagController extends ActiveController
{

    public $modelClass = 'app\models\book';
    public $modelClass1 = 'app\models\User';

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

                'interact' => ['get'],
                // 'view' => ['get'],
                // 'create' => ['post'],
                // 'update' => ['put'],
                // 'delete' => ['delete'],
                'loginmethod' => ['post'],
                'enternewbook' => ['post'],
                'seebook' => ['get'],
                'enteruser' => ['post'],
                'seealluser' => ['get'],
                'updatepassword' => ['post'],
                'maketransaction' => ['post'],
                'returnbook' => ['post'],
                'deleteuser' => ['post'],
                'increasebookcount' => ['post'],
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
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD','OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 3600,
                'Access-Control-Allow-Origin' => ['*'],
            ],
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = [
            'options',
            'loginmethod',
            // 'signup',
            // 'confirm',
            // 'password-reset-request',
            // 'password-reset-token-verification',
            // 'password-reset',
            'seebook',
            'enteruser',
            'seealluser',
            'enternewbook',
            'updatepassword',
            'maketransaction',
            'returnbook',
            'deleteuser',
            'increasebookcount',
        ];

        // setup access
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => ['index','create','view', 'update', 'delete'], //only be applied to
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['seebook','enteruser'],
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

    public function actionOptions($id = null)
    {
        return 'ok';
    }

    public function actionEnteruser()
    {
        $userone = new Usertable();
        $role    = new Role();
        $body    = file_get_contents('php://input');

        $user = json_decode($body);

        foreach($user as $ky => $vl){
            $arruser[$ky ] =  $vl;
        }

        $verifiedusername = $userone->varifyusername($arruser['u_username']);
        if($verifiedusername == 0){ return "username exists.!";}

        $verifiedemail = $userone->varifyemail($arruser['u_email']);
        if($verifiedemail == 0){ return "email exists.!";}

        $verifiedphno = $userone->varifyphonenumber($arruser['u_phno']);
        if($verifiedphno == 0){ return "Phone Number exists.!";}

        if(($verifiedusername && $verifiedemail && $verifiedphno) == 1){
            try{
                $usrmodel = new User();
                $usrmodel->generateAccessToken();
                $password = $arruser['u_password'];
                $hash = Yii::$app->getSecurity()->generatePasswordHash($password);

                $userone -> u_name = $arruser['u_name'];
                $userone -> u_username = $arruser['u_username'];
                $userone -> u_email = $arruser['u_email'];
                $userone -> u_password = $hash;
                $userone -> u_phno = $arruser['u_phno'];
                $userone -> u_bookissued = 0;
                $userone -> u_updateddate = date('Y-m-d H:i:s');
                $userone -> u_createddate = date('Y-m-d H:i:s');
                $userone -> u_dob = $arruser['u_dob'];
                $userone -> access_token = $usrmodel->access_token;
                $userone -> access_token_exp_date = date('Y-m-d H:i:s',$usrmodel->access_token_expired_at);
                $userone -> save();

                $user_id = $userone -> returnuserid($arruser['u_username']);

                $role -> r_id = $user_id;
                $role -> save();
                return "user registered";
                }
                catch(Exception $e){
                    throw new HttpException( 403 , $e->getMessage());
                }
        }else{
            throw new HttpException(422,"username or email or phone number exists.!");
        }


    }


    public function actionLoginmethod()
    {
        $role = new Role();

        $username = Yii::$app->request->post('username'); 
        $pwd      = Yii::$app->request->post('password');

        try{

            $sql  = "select u_password from users_table where u_username = '$username'";
            $rslt = Yii::$app->db->createCommand($sql)->query()->read();

            $pwdrslt = Yii::$app->getSecurity()->validatePassword($pwd,$rslt["u_password"]);

            if($pwdrslt == 1 ){

                $updateuser = new user;
                $updateuser -> generateAccessToken();

                $new_access_token = $updateuser->access_token;
                $new_exp_date = date('Y-m-d H:i:s',$updateuser -> access_token_expired_at);

                $sql = "UPDATE users_table
                        SET access_token = '$new_access_token',
                        access_token_exp_date = '$new_exp_date',
                        u_updateddate = now()
                        WHERE u_username = '$username'";

                Yii::$app->db->createCommand($sql)->execute();

            }

            $userlogin = new Usertable();
            $rslt = $userlogin -> senddetailswhenlogin($username);

            $ans['user_id']  = $rslt['u_id'];
            $ans['username'] = $rslt['u_username'];
            $ans['name']     = $rslt['u_name'];
            $ans['role_id']  = $role->returnroleid($rslt['u_id']);
            $ans['token']    = $rslt['access_token'];


            $msg = ["message" => "Login successfully",
                    "data" => $ans
                    ];

            if($username == $rslt['u_username'] && $pwdrslt == 1){
                return $msg;
            }
            else{
                throw new HttpException(422, "password does not match");
            }
        }catch(exception $e){
            throw new HttpException(403,$e->getMessage());
        }



    }


    public function actionSeebook()
    {

        $userr = new Usertable();

        $username = Yii::$app->request->get('username');

        if($userr->validateuser($username) == 1){

            try{
            $allbook = new Book();
            return $allbook -> seeallbook();
            }
            catch(Exception $e){
                return $e->getMessage();
            }

        }else{
            throw new HttpException(422,"invalid user");
        }



    }

    public function actionSeealluser()
    {
        $userr = new Usertable();
        $role  = new Role();

        $username = Yii::$app->request->get('username');

        $user_id  = $userr->returnuserid($username);
        $role_id  = $role -> returnroleid($user_id);

        try{
            if($userr->validateuser($username) == 1 && $role_id == 0){

                $alluser = new Usertable();
                return $alluser -> seealFluser();

            }else{
                throw new HttpException(422,"invalid user");
            }
        }catch(exception $e){
            throw new HttpException(403,$e->getMessage());
        }
    }

    public function actionEnternewbook()
    {
        $newbook = new Book();
        $ispn    = new bookscopy();
        $body    = file_get_contents('php://input');

        $book = json_decode($body);

        try{
        foreach($book as $ky => $vl){
            $arrbook[$ky ] =  $vl;
        }

        $newbook -> b_title = $arrbook['b_title'];
        $newbook -> b_author = $arrbook['b_author'];
        $newbook -> b_catagory = $arrbook['b_catagory'];
        $newbook -> b_description = $arrbook['b_description'];
        $newbook -> b_count = $arrbook['b_count'];
        $newbook -> save();


        $latest_book_id = $newbook -> returnlatestid();

        $ispntask = $ispn -> addcopies($latest_book_id, $arrbook['b_count']);

        if($ispntask == "ispn created"){
            return $ispntask;
        }
        elseif($ispntask == "something went wrong in copies table"){
            return $ispntask;
        }
        else {
            return $ispntask;
        }

        }
        catch(Exception $e){

            throw new HttpException(401, $e->getMessage());
        }

    }

    public function actionUpdatepassword()
    {
        $userr = new Usertable();

        $username    = Yii::$app->request->post('username');
        $newpassword = Yii::$app->request->post('newpassword');
        try{
            if($userr->validateuser($username) == 1){
                try{
                    $alluser = new Usertable();
                    return $alluser -> updatepassword($username, $newpassword);
                    }
                    catch(Exception $e){
                        echo $e->getMessage();
                    }
            }else{
                throw new HttpException(422,"invalid user");
            }
            }catch(Exception $e){
                throw new HttpException(403,$e->getMessage());
            }
    }

    public function actionMaketransaction()
    {
        $date           = strtotime("+7 day");
        $exp_returndate = date('Y-m-d', $date);
        $currentdate    = date('Y-m-d');

        $newtransaction = new Libtransaction();
        $userr          = new Usertable();
        $ispn           = new bookscopy();

        $username = Yii::$app->request->get('username');

        $body = file_get_contents('php://input');

        $transaction = json_decode($body);



        try{


            if($userr->validateuser($username) == 1){

                foreach($transaction as $ky => $vl){
                    $arrtrans[$ky ] =  $vl;
                }

                $transaction_userid = $arrtrans["user_id"];
                $transaction_bookid = $arrtrans["book_id"];

                $copy_id = $ispn -> seeavailability($transaction_bookid);

                if($copy_id == "No copy available of given Copy"){
                    throw new HttpException(405,$copy_id);
                }else{

                    if($userr->getissuedbook($transaction_userid) >= 3){
                        $msg = "maximum books issued";
                        throw new HttpException(403,$msg);
                    }
                    if($newtransaction->cantransact($transaction_userid,$transaction_bookid) == 0){
                        $msg = "same book can not be issues again to same user";
                        throw new HttpException(403,$msg);}

                    $newtransaction -> user_id = $transaction_userid;
                    $newtransaction -> book_id = $transaction_bookid;
                    $newtransaction -> copy_id = $copy_id;
                    $newtransaction -> issued_date = $currentdate;
                    $newtransaction -> exp_returndate = $exp_returndate;
                    $newtransaction -> t_createddate = $currentdate;
                    $newtransaction -> t_updateddate = $currentdate;
                    $newtransaction -> save();

                    $ispncreated = $ispn ->  makeunavailable($copy_id);
                    $userupdated = $userr -> updatebookcount($transaction_userid);

                    if(($ispncreated && $userupdated) == 1){
                        return "All updation done";
                    }else{
                        throw new HttpException(403,"some error occured");
                    }
                }



            }else{
                throw new HttpException(422,"invalid user");
            }



        }catch(Exception $e){
                throw new HttpException(403,$e->getMessage());

        }
    }

    public function actionReturnbook()
    {

        $returntrans = new Libtransaction();
        $userr       = new Usertable();
        $book        = new Book();
        $copy        = new Bookscopy();
        $role        = new Role();

        $body = file_get_contents('php://input');
        $returntransaction = json_decode($body);

        $username = Yii::$app->request->get('username');
        $user_id  = $userr->returnuserid($username);
        $role_id  = $role -> returnroleid($user_id);

        try{
            foreach($returntransaction as $ky => $vl){
                $arrtrans[$ky] =  $vl;
            }

            if($userr->validateuser($username) == 1 && $role_id == 0){

                $returntrans_userid = $arrtrans['user_id'];
                $returntrans_bookid = $arrtrans['book_id'];

                $act_returneddate   = $arrtrans['act_returneddate'];

                $return_transid = $returntrans -> returntransid($returntrans_userid,$returntrans_bookid);
                $process        = $returntrans -> returnbook($return_transid,$act_returneddate);
                $copy_id        = $returntrans -> returncopyid($return_transid);

                $available          = $copy        -> makeavailable($copy_id);
                $fine               = $returntrans -> calculatefine($return_transid);
                $fineintranstable   = $returntrans -> addfineintable($return_transid,$fine);
                $addfineinusertable = $userr       -> addfineintable($returntrans_userid,$fine);

                if(($process && $fineintranstable && $addfineinusertable && $available) == 1){
                    $msg['message'] = "All updation Done";
                    $msg['fine'] = $fine;
                    return $msg;
                }else{
                    return "somthing went wrong";
                }
            }



        }catch(Exception $e){
            throw new HttpException(409,$e->getMessage());
        }



    }

    public function actionDeleteuser()
    {
        $deluser    = new Usertable();
        $role       = new Role();

        $body       = file_get_contents('php://input');
        $deleteuser = json_decode($body);

        try{
            foreach($deleteuser as $ky => $vl){
                $arrdel[$ky] =  $vl;
            }

            $delete_userid = $arrdel['user_id'];

            $username = Yii::$app->request->get('username');
            $user_id  = $deluser->returnuserid($username);
            $role_id  = $role -> returnroleid($user_id);

            if($deluser->validateuser($username) == 1 && $role_id == 0){

                $finestatus = $deluser -> checkforfine($delete_userid);
                if($finestatus == 1){

                    $bookissued = $deluser -> getissuedbook($delete_userid);
                    if($bookissued == 0){

                        $userdeleted = $deluser -> deleteuser($delete_userid);
                        if($userdeleted == 1){
                            $msg = "user deleted";
                            return $msg;
                        }else{
                            $msg = "something went wrong while deleting";
                            return $msg;
                        }

                    }else{
                        $msg = "user have some book issued already.";
                        throw new HttpException(403,$msg);
                    }

                }else{
                    $msg = "user has fine of: ".$finestatus;
                    throw new HttpException(403,$msg);
                }

            }else{
                throw new HttpException(422,"invalid user");
            }
        }catch(Exception $e){
            throw new HttpException(404,$e->getMessage());
        }
    }

    public function actionIncreasebookcount()
    {

        $userr      = new Usertable();
        $role       = new Role();
        $updatebook = new Book();
        $newcopy    = new Bookscopy();

        $body       = file_get_contents('php://input');
        $deleteuser = json_decode($body);

        $username = Yii::$app->request->get('username');
        $user_id  = $userr->returnuserid($username);
        $role_id  = $role -> returnroleid($user_id);

        if($userr->validateuser($username) == 1 && $role_id == 0){
            try{
                foreach($deleteuser as $ky => $vl){
                    $arrnewcopy[$ky] =  $vl;
                }
                $updatebook_id = $updatebook -> returnbookid($arrnewcopy['b_title']);
                $maxcopynumber = $newcopy -> findmaxcopy($updatebook_id);
                $addcount       = $arrnewcopy['newcopy_added'];

                $copyprocess = $newcopy -> addnewcopies($updatebook_id,$addcount,$maxcopynumber);
                $bookprocess = $updatebook ->updatebookcount($updatebook_id,$addcount);
                if($copyprocess && $bookprocess == 1){
                    $msg = "ispn created";
                    return $msg;
                }else{
                    $msg = "something went wrong";
                    throw new HttpException(403,$msg);
                }

            }catch(Exception $e){
                throw new HttpException(403,$e->getMessage());
            }
        }else{
            throw new HttpException(422,"invalid user");

        }
    }


}
?>
