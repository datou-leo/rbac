<?php
namespace app\index\controller;
use app\index\model\Access;
use app\index\model\User;
use think\Controller;

use think\Request;
use think\Config;
use think\Session;
use app\index\model\User as UserModel;
use app\index\model\User_Role;
use app\index\model\Role_Access;
use app\index\model\App_Access_Log;

class BaseController extends  Controller
{


    protected $beforeActionList = [
        'beforeLogin'=> ['except'=>'vlogin,login,forbidden']
    ];

    protected $ignore_urls=['error/forbidden','user/vlogin','user/login'];

    /**
     * 登录前检查用户权限
     * @return boolean
     */
    public function beforeLogin(){

        if(!$this->is_login()){
            if(Request::instance()->isAjax()){
                $this->renderJSON([],"未登陆，请返回登录",-302);
            }else{
                $this->redirect('user/login',[]);
            }
        }
        $access_urls = $this->getUserAccess();
        $controller_name = strtolower(Request::instance()->controller());
        $action_name = strtolower(Request::instance()->action());
        $current_url = "/".$controller_name."/".$action_name;
        $user = current_user();
        $user = User::find($user["uid"])->toArray();

        $app_access_log = new App_Access_Log();



        //记录登陆日志
        $get_params =input('get.');
        $post_params =input('post.');
        $data["uid"] = $user["id"];
        $data["target_url"] = isset( $_SERVER['REQUEST_URI'] )?$_SERVER['REQUEST_URI']:'';
        $data["query_params"] = json_encode( array_merge( $post_params,$get_params ) );
        $data["ua"]  = isset($_SERVER['HTTP_USER_AGENT'] )?$_SERVER['HTTP_USER_AGENT']:'';
        $data["ip"] = isset($_SERVER['REMOTE_ADDR'] )?$_SERVER['REMOTE_ADDR']:'';
        $data["created_time"]=date("Y-m-d H:i:s");
        $app_access_log->data($data);
        $app_access_log->save();


        if(in_array($current_url,$this->ignore_urls)){

        }else if($user["is_admin"]){

        }else if(!in_array($current_url,$access_urls)){
            $this->redirect('error/forbidden',[]);
        }


    }

    /**
     * 获取当前登陆用户权限
     * @return boolean
     */
    private function getUserAccess(){
        $access_urls = [];
        $user = current_user();
        $user_role_list = User_Role::all(['uid'=>$user["uid"]]);
        $related_role_ids = array_column(Collection($user_role_list)->toArray(),'role_id');
        if($related_role_ids){
            $role_access_list = Role_Access::all(['role_id'=>['in',$related_role_ids]]);
            $related_access_ids = array_column(Collection($role_access_list)->toArray(),'access_id');
            $access_list = Access::all($related_access_ids);
            foreach($access_list as $access){
                $temp_urls = json_decode($access["urls"],true);
                $access_urls=  array_merge($access_urls,$temp_urls);
            }
        }
        return $access_urls;
    }


    /**
     * 统一返回json数据
     * @access protected
     */
    protected  function renderJSON($data=[],$msg="ok",$code=200){
        echo json_encode([
            "code"=>$code,
            "msg"=>$msg,
            "data"=>$data,
            "req_id"=>uniqid()
        ]);exit();
    }

    /**
     * 检测用户是否登录
     * @access protected
     * @return mixed
     */
    protected function is_login(){
        $user = $this->sessionGet('user');
        if (empty($user)) {
            return false;
        } else {
            return  $this->sessionGet('user_sign') == $this->data_auth_sign($user) ? $user : false;
        }
    }




    /**
     * 数据签名认证
     * @access protected
     * @param  array  $data 被认证的数据
     * @return string       签名
     */
    protected  function data_auth_sign($data) {
        $code = http_build_query($data); //url编码并生成query字符串
        $sign = sha1($code); //生成签名
        return $sign;
    }

    /**
     * 读取session
     * @access protected
     * @param  string  $path 被认证的数据
     * @return mixed
     */
    protected function sessionGet($path =''){
        $session_prefix = Config::get('rbac.session_prefix');
        $user           = Session::get($session_prefix.$path);
        return $user;
    }
}
