<?php
/**
 * Created by PhpStorm.
 * User: HaloBear
 * Date: 2018/3/2
 * Time: 11:41
 */
namespace app\home\controller;

use think\Db;

class Download extends Base
{

    public function index()
    {
        return $this->fetch();
    }

}