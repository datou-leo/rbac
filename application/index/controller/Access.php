<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use app\index\model\Access as AccessModel;

class Access extends  BaseController
{
    public function index()
    {
        $list = AccessModel::all();
        $this->assign('list',$list);
        $this->view->engine->layout('layout/main');
        return $this->fetch('index');
    }

    public function set(){
        $id = Request::instance()->param('id');
        if(Request::instance()->isGet()){
            if(!empty($id)){
                $access = AccessModel::find($id)->toArray();
                $this->assign('access',$access);
            }
            $this->view->engine->layout('layout/main');
            return $this->fetch('set');
        }else{
            if(empty($id)){
               //添加
                $data= input("post.");
                $data["status"]=1;
                $data["created_time"]=date("Y-m-d H:i:s");
                $data["urls"]= json_encode(explode("\n",input("post.urls")));
                $access = new AccessModel();
                $a = $access->where('title', $data["title"])->find();
                if(!empty($a)){
                    $this->renderJSON([],"权限名已存在~~",-1);
                }
                $access->data($data);
                $access->save();
                $this->renderJSON([],"操作成功~~",200);
            }else{
                //修改
                $id= input("post.id");
                $title = input("post.title");
                $data["title"]=$title;
                $data["urls"]= json_encode(explode("\n",input("post.urls")));
                $data["updated_time"]=date("Y-m-d H:i:s");
                $access = new AccessModel();
                $access->allowField(true)->save($data,['id' => $id]);
                $this->renderJSON([],"操作成功~~",200);
            }
        }

    }

}
