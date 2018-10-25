<?php
namespace app\index\controller;
use think\Controller;

class Test extends  BaseController
{
    public function page1()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('page1');
    }

    public function page2()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('page2');
    }

    public function page3()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('page3');
    }

    public function page4()
    {
        $this->view->engine->layout('layout/main');
        return $this->fetch('page4');
    }

}
