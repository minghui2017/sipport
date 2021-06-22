<?php

namespace app\admin\controller;
use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use think\captcha\facade\Captcha;
use think\facade\Env;
use think\facade\Db;
use think\Request;

class Weixin extends AdminController
{
    public function weixin(){
        $code=input('post.code');

        if(empty($code)){
            $this->error('code不能为空');
        }
        //从数据库查apid appsecret
        $appid='wx46e9cfa0d24985b9';
        $appsecret='eccdffad01471b1388128d1090b49fa5';
        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';
        //json对象变成数组
        $res=json_decode(file_get_contents($url),true);

        if(!empty($res['errcode'])){
            $this->error("code过期，请重试");
        }

        $access_token=$res['access_token'];
        $openid=$res['openid'];
        $urlyonghu='https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid;
        $user=json_decode(file_get_contents($urlyonghu),true);


        // 可以打印出用户的基本信息存入数据
        // 查询unionid是不是唯一 有直接登录=====没有存数据库

        $res = Db::name('user_weixin')
            ->where("unionid",$user['unionid'])
            ->find();


        $att['unionid']=$user['unionid'];
        $att['openid'] =$user['openid'];
        $att['nickname'] =$user['nickname'];
        $att['sex']=$user['sex'];
        $att['province']=$user['province'];
        $att['city']=$user['city'];
        $att['country']=$user['country'];
        $att['headimgurl']=$user['headimgurl'];
        $att['token']=md5(time());


        if(!empty($res)){
            //同步
            $add = Db::name('user_weixin')
                ->where("unionid",$user['unionid'])
                ->save($att);
            if(empty($res['uid'])){
                $this->success('ok',['uw_id'=>$res['id']],'120');
            }else{
                $data= $this->getUserInfoByWhere(['u_id'=>$res['uid']]);
                if(empty($data)){
                    $this->error('未找到用户信息');
                }
                $SetData=[
                    'token'=>md5(time()),
                    'token_time' => time(),//获取时间戳
                    'login_time' => date('Y-m-d H:i:s',time())
                ];
                Db::name('user')->where(['u_id'=>$data['u_id']])->update($SetData);

                $data['company_status'] = empty($data['company_user_id']) ? -1: $data['company_status']; // -1:未加入企业
                $data['token']=$SetData['token'];
                $data['password']=null;
                if($this->user['u_role']==0){
                    $data['user_count']=Db::name('company_user')->where(['company_id'=>$data['company_id']])->count();
                }else{
                    $data['user_count']=0;
                }
                $this->success('登录成功',$data);
            }

        }else{
            $res = Db::name('user_weixin')
                ->insertGetId($att);
            return $this->success('ok',['uw_id'=>$res],'120');

        }


    }

    public function index(){
        echo "123123";
    }
}
