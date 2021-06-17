<?php

namespace app\admin\controller\support;

use think\Request;
use app\admin\model\SupportTeamMecha;
use app\admin\model\SystemQuick;
use app\common\controller\AdminController;
use think\App;
use think\facade\Env;

class Admin extends AdminController
{
    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SupportTeamMecha();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
        if (input('selectFields')) {
            return $this->selectList();
        }
        list($page, $limit, $where) = $this->buildTableParames();
        $count = $this->model
            ->where($where)
            ->count();
        $list = $this->model
            ->where($where)
            ->page($page, $limit)
            ->select();
        $data = [
            'code'  => 0,
            'msg' => '',
            'count' => $count,
            'data'  => $list,
        ];

        return json($data);
    }

        return $this->fetch();
    }

    public function add(){
       dd(123);
    }
}