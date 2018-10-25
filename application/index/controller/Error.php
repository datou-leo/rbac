<?php
namespace app\index\controller;
use think\Controller;

class Error extends  BaseController
{
    public function forbidden()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('index');
    }
}