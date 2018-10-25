<?php
namespace app\index\controller;

use app\index\model\User_Role;
use think\model\Collection;
use think\Request;
use think\Config;
use think\Session;
use app\index\model\User as UserModel;
use app\index\model\Role as RoleModel;
use think\Db;
class User extends  BaseController
{
    public function login()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('login');
    }


    /**
     * 用户伪登录
     */
    public function vlogin(){
        $uid = Request::instance()->param('uid');
        if(!$uid){
            $this->redirect('user/login',[]);
        }

        $user_info = UserModel::find($uid);
        if(empty($user_info)){
            $this->redirect('user/login',[]);
        }

        $session_prefix = Config::get('rbac.session_prefix');
        $user           = [
            'uid'       => $uid,
            'name'  => $user_info["name"],
            'time'      => time()
        ];
        Session::set($session_prefix.'user',$user);

        Session::set($session_prefix.'user_sign',$this->data_auth_sign($user));
        $this->redirect('index/index',[]);
    }

    public function index()
    {
        $list = UserModel::all();
        $this->assign('list',$list);
        $this->view->engine->layout('layout/main');
        return $this->fetch('index');
    }

    public function set(){
        $id = Request::instance()->param('id');
        if(Request::instance()->isGet()){

            $role_list = RoleModel::all();
            $this->assign('role_list',$role_list);


            if(!empty($id)){
                $user = UserModel::find($id)->toArray();
                $this->assign('user',$user);
                $user_role_list = User_Role::all(['uid'=>$id]);
                $related_role_ids = join(',',array_column(Collection($user_role_list)->toArray(),'role_id'));
                $this->assign('related_role_ids',$related_role_ids);
            }
            $this->view->engine->layout('layout/main');
            return $this->fetch('set');
        }else{
            if(empty($id)){
                //添加用户
                $data= input("post.");
                // 启动事务
                Db::startTrans();
                try{
                    $user = new UserModel();
                    $r = $user->where('name', $data["name"])->find();
                    if(!empty($r)){
                        $this->renderJSON([],"角色名已存在~~",-1);
                    }
                    $user->data(["name"=>$data["name"],"email"=>$data["email"],"status"=>1,"is_admin"=>0,"created_time"=>date("Y-m-d H:i:s")]);
                    $user->save();
                    $user_role = new User_Role();
                    $list = [];
                    foreach($data["role_ids"] as $role_id){
                        $list[] = ["uid"=>$user->id,"role_id"=>$role_id,"created_time"=>date("Y-m-d H:i:s")];
                    }
                    $user_role->saveAll($list);
                    // 提交事务
                    Db::commit();
                    $this->renderJSON([],"操作成功~~",200);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->renderJSON([],"操作失败~~",-1);
                }
            }else{
                //修改用户

                $data = input("post.");
                $id= input("post.id");
                $data["updated_time"]=date("Y-m-d H:i:s");

                //print_r($data);die;
                // 启动事务
                Db::startTrans();
                try{

                    $user = new UserModel();
                    $user->allowField(true)->save($data,['id' => $id]);

                    $user_role_list = User_Role::all(['uid'=>$id]);
                    $array_A = array_column(Collection($user_role_list)->toArray(),'role_id');
                    $array_B = empty($data["role_ids"])?array():$data["role_ids"];
                    $array_C = array_diff($array_A,$array_B);
                    $array_D = array_diff($array_B,$array_A);
                    $user_role = new User_Role();
                    if(!empty($array_C)){
                        $user_role_ids =[];
                        foreach($array_C as $role_id) {
                            $list = User_Role::all(['uid' => $id, "role_id" => $role_id]);
                            $ids = array_column(Collection($list)->toArray(), 'id');
                            $user_role_ids = array_merge($user_role_ids, $ids);
                        }
                        User_Role::destroy($user_role_ids);
                    }

                    if(!empty($array_D)){
                        $list = [];
                        foreach($array_D as $role_id){
                            $list[] = ["uid"=>$id,"role_id"=>$role_id,"created_time"=>date("Y-m-d H:i:s")];
                        }
                        $user_role->saveAll($list);
                    }

                    // 提交事务
                    Db::commit();
                    $this->renderJSON([],"操作成功~~",200);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->renderJSON([],"操作失败~~",-1);
                }


            }
        }

    }




}
