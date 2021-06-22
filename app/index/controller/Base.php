<?php

namespace app\index\controller;


use think\facade\Db;
use think\route\dispatch\Response;
class Base
{
    protected $user,$u_id;
    public function __construct(){
        $WhiteList=['/index/whatch/weixin','/index/whatch/outLogin','/index/whatch/getUserDetail'];
        if(!in_array(request()->url(),$WhiteList)){
            $token=empty(input('token')) ? request()->header('token') : input('token');
            if(empty($token)||$token=='undefined' ){//判断cookie中token是否为空
                $this->reject('数据无效，请重新登录');
            }
            $user = $this->getUserInfoByWhere(['token' =>$token]);
            if (empty($user)) {
                $this->reject('数据失效，请重新登录。');
            }

            if (empty($user['token_time'])||$user['token_expire'] < time()) {
                $this->reject('token过期');
            }
//            $SetData=[
//                'token_time' =>time(),//获取时间戳
//            ];
//            Db::name('user')->where(['u_id'=>$user['u_id']])->update($SetData);
//            $map['user_id']=$user['u_id'];
//            $map['company_id']=$user['company_id'];
//            $map['company_status']=1;
//            $count=Db::name('company_user')->where($map)->count();
            //0 禁用 1启用 2待审核 3拒绝加入

            $this->user=$user;
            $this->u_id=$user['u_id'];
        }
    }

    public function getUserInfoByWhere($where = array()){

        $user = Db::name('support_user')->where($where)->find();

        return $user;
    }

    protected function success($msg='操作成功',$data=null,$code=1){
        $cb = [
            'msg'=>$msg,
            'data'=>$data,
            'code'=>$code,
        ];
        $this->ajaxReturn($cb);
    }

    protected function error($msg='操作失败',$code=0,$data=null){
        $cb = [
            'msg'=>$msg,
            'data'=>$data,
            'code'=>$code,
        ];
        $this->ajaxReturn($cb);
    }

    protected function reject($msg='token失效',$data=null,$code=-100){
        $cb = [
            'msg'=>$msg,
            'data'=>$data,
            'code'=>$code,
        ];
        $this->ajaxReturn($cb);
    }

    public function   ajaxReturn($data){
        header('Content-Type:application/json');//这个类型声明非常关键
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }
}
