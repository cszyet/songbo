<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch('index');
    }
    public function adult_exam ()
    {
        return $this->fetch('adult_exam');
    }
    public function diy_exam ()
    {
        return $this->fetch ('diy_exam');
    }
}
