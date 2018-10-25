<?php
namespace app\index\controller;
use think\Controller;

class Index extends  BaseController
{
    public function index()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('index');
    }
}
