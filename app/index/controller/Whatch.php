<?php


namespace app\index\controller;

use think\Request;
use think\facade\Db;
use think\facade\Env;

class Whatch extends Base
{
//    public function getUserDetail(){
//
//        $redirect_url=urlencode("http://oo.admint.com/index/whatch/weixin");
//
//        $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx46e9cfa0d24985b9&redirect_uri=&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";
//        return redirect($url);
//    }
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

        $res = Db::name('support_user')
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
        $att['token'] = md5(time());
        $att['token_time'] = time();
        $att['token_expire']=strtotime(date('Y-m-d H:i:s').'+1 month');
        $att['login_time'] = date('Y-m-d H:i:s',time());

        if(!empty($res)){
            //同步
            $add = Db::name('support_user')
                ->where("unionid",$user['unionid'])
                ->save($att);
            if(empty($res['u_id'])){
                $this->success('ok',['u_id'=>$res['u_id']],'120');
            }else{
                $data= $this->getUserInfoByWhere(['u_id'=>$res['u_id']]);
                if(empty($data)){
                    $this->error('未找到用户信息');
                }
                $SetData=[
                    'token'=>md5(time()),
                    'token_time' => time(),//获取时间戳
                    'token_expire' =>strtotime(date('Y-m-d H:i:s'). '+ 1 month' ),
                    'login_time' => date('Y-m-d H:i:s',time())
                ];
                Db::name('support_user')->where(['u_id'=>$data['u_id']])->update($SetData);
                $data['token']=$SetData['token'];
                $this->success('登录成功',$data);
            }
        }else{
            $res = Db::name('support_user')
                ->insertGetId($att);
            $data = $this->getUserInfoByWhere(['u_id'=>$res]);
            return $this->success('ok',['u_id'=>$res]);
        }


    }

    public function phone(){
        $code=input('post.code');
        $appid='wx46e9cfa0d24985b9';
        $appsecret='eccdffad01471b1388128d1090b49fa5';
        $url='https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';
        $user=json_decode(file_get_contents($url),true);
        if(!empty($user)){
            $res = Db::name('support_user')->where(['u_id'=>$this->user['u_id']])->update(['phone'=>$user['phoneNumber']]);
        }
        if($res){
            return $this->success('成功',$user['phoneNumber']);
        }else{
            return $this->error('失败');
        }
    }

    //退出登录
    public function outLogin(){
        //修改token过期时间
        $update['token_expire'] = time();
        //启动事务
        Db::startTrans();
        try{
            Db::name('support_user')->where('u_id',$this->user['u_id'])->update($update);
            Db::commit();
        } catch (\Exception $e){
            //事务回滚
            Db::rollback();
            return $this->error('操作失败'.$e->getMessage());
        }
        return $this->success('操作成功',$update);
    }
}
