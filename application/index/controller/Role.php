<?php
namespace app\index\controller;
use app\index\model\Access;
use think\Controller;
use think\Request;
use app\index\model\Role as RoleModel;
use app\index\model\Access as AccessModel;
use app\index\model\Role_Access;
use think\Db;

class Role extends  BaseController
{
    public function index()
    {
        $list = RoleModel::all();
        $this->assign('list',$list);
        $this->view->engine->layout('layout/main');
        return $this->fetch('index');
    }

    public function set(){
        $id = Request::instance()->param('id');
        if(Request::instance()->isGet()){
            if(!empty($id)){
                $role = RoleModel::find($id)->toArray();
                $this->assign('role',$role);
            }
            $this->view->engine->layout('layout/main');
            return $this->fetch('set');
        }else{
            if(empty($id)){
               //添加
                $data= input("post.");
                $data["status"]=1;
                $data["created_time"]=date("Y-m-d H:i:s");
                $role = new RoleModel();
                $r = $role->where('name', $data["name"])->find();
                if(!empty($r)){
                    $this->renderJSON([],"角色名已存在~~",-1);
                }
                $role->data($data);
                $role->save();
                $this->renderJSON([],"操作成功~~",200);
            }else{
                //修改
                $id= input("post.id");
                $name = input("post.name");
                $data["name"]=$name;
                $data["updated_time"]=date("Y-m-d H:i:s");
                $role = new RoleModel();
                $role->allowField(true)->save($data,['id' => $id]);
                $this->renderJSON([],"操作成功~~",200);
            }
        }

    }

    public function access(){
        $id  = Request::instance()->param('id');

        if(Request::instance()->isGet()){

            $access_list = AccessModel::all();
            $this->assign('access_list',$access_list);

            $role_access__list = Role_Access::all(['role_id'=>$id]);
            $related_access_ids = join(',',array_column(Collection($role_access__list)->toArray(),'access_id'));
            $this->assign('related_access_ids',$related_access_ids);


            $role = RoleModel::find($id)->toArray();
            $this->assign('role',$role);
            $this->view->engine->layout('layout/main');
            return $this->fetch('access');
        }else{
            // 启动事务
            Db::startTrans();
            try{
                $data = input("post.");
                $id= input("post.id");
                $role_access_list = Role_Access::all(['role_id'=>$id]);
                $array_A = array_column(Collection($role_access_list)->toArray(),'access_id');
                $array_B = empty($data["access_ids"])?array():$data["access_ids"];
                $array_C = array_diff($array_A,$array_B);
                $array_D = array_diff($array_B,$array_A);
                $role_access = new Role_Access();
                if(!empty($array_C)){
                    $role_access_ids =[];
                    foreach($array_C as $access_id) {
                        $list = Role_Access::all(['role_id' => $id, "access_id" => $access_id]);
                        $ids = array_column(Collection($list)->toArray(), 'id');
                        $role_access_ids = array_merge($role_access_ids, $ids);
                    }
                    Role_Access::destroy($role_access_ids);
                }

                if(!empty($array_D)){
                    $list = [];
                    foreach($array_D as $access_id){
                        $list[] = ["role_id"=>$id,"access_id"=>$access_id,"created_time"=>date("Y-m-d H:i:s")];
                    }
                    $role_access->saveAll($list);
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
