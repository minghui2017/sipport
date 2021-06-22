<?php


namespace app\index\controller;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\validate\User;
use think\Request;
use think\facade\Env;
use think\facade\Db;
class Team extends Base
{
    //团队申请页面
    public function teamAddList()
    {
        $dataMecha = Db::name('support_team_mecha')->select();
        $dataSer = Db::name('support_team_service')->select();
        $data['mecha'] = $dataMecha;
        $data['ser'] = $dataSer;
        return $this->success($data);
    }

    //团队申请
    public function teamAdd()
    {
        $data = input('post.');
        $u_id = $this->user['u_id'];
        $data['u_id'] = $u_id;
        $data['team_time'] = time();
        $data['team_num'] = $this->no_repe_number();
        $validate = new User();
        $result = $validate->scene("AddTeam")->check($data);
        if (!$result) {
            $this->error($validate->getError());
        }
        //查询是否为志愿者
        $count = Db::name('support_user')->where('u_id', $this->user['u_id'])->where('status',1)->find();

        if(empty($count)){
            return $this->error('当前您还不是志愿者无法创建支援团队，请先申请志愿者');exit;
        }

        $find = Db::name('support_team')->where('u_id', $u_id)->find();
        if (!empty($find)) {
            return $this->error('您已经创建一个团队了');
        }
        //验证码验证
        $where['code_phone'] = $data['u_phone'];
        $where['code'] = $data['code'];
        $where['code_status'] = 0;
        $codeData = Db::name('support_code')->where($where)->find();
        if (empty($codeData)) {
            $this->error('验证码错误');
        }
        if ($codeData['create_time'] + 600 < time()) {
            $this->error('验证码失效');
        }

        Db::startTrans();
        try{
            unset($data['code']);
            $res = Db::name('support_team')->insertGetId($data);
            //同步到自己的团队列表
            $add['team_id']=$res;
            $add['user_id']=$u_id;
            $add['create_time']=time();
            $add['create_user_name']=$data['team_person'];
            $add['create_user_phone']=$data['u_phone'];
//            $add['is_monitor'] =1;
            //申请团队通过后更改状态
            Db::name('support_team_user')->save($add);
            Db::name('support_code')->where($where)->update(['code_status' => 1]);
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            return $this->error($exception->getMessage());
        }
        return $this->success('申请成功');
    }

    //志愿者申请
    public function volunteerAdd()
    {
        $data = input('post.');
        $find = [
            'u_phone' => $data['u_phone'],
            'u_name' => $data['u_name'],
            'sex' => $data['sex'],
            'u_address' => $data['u_address'],
            'status' => 2
        ];
        $validate = new User();
        $result = $validate->scene("AddUser")->check($data);
        if (!$result) {
            $this->error($validate->getError());
        }

        $where['code_phone'] = $data['u_phone'];
        $where['code'] = $data['code'];
        $where['code_status'] = 0;
        $codeData = Db::name('support_code')->where($where)->find();

        if (empty($codeData)) {
            $this->error('验证码错误');
        }
        if ($codeData['create_time'] + 600 < time()) {
            $this->error('验证码失效');
        }


        $count = Db::name('support_user')->where('u_id', $this->user['u_id'])->find();
        if (empty($count['status'])) {
            Db::startTrans();
            try {
                Db::name('support_code')->where($where)->update(['code_status' => 1]);
                Db::name('support_user')->where('u_id', $this->user['u_id'])->update($find);
                Db::commit();
            } catch (\Exception $e) {
                //事务回滚
                Db::rollback();
                return $this->error('发出申请失败' . $e->getMessage());
            }
            return $this->success('发出申请成功');

            //消息日志

        } else if ($count['status'] = 2) {
            return $this->error('已经在申请当中');
            //是否需要消息
        } else {
            return $this->error('已经申请过');
        }

    }
    //群号
    public function no_repe_number($start = 0, $end = 9, $len = 6)
    {
        $co = 0;
        $arr = $reArr = array();
        while ($co < $len) {
        $arr[] = mt_rand($start, $end);
        $reArr = array_unique($arr);
        $co = count($reArr);
        }
        $reArr = implode($reArr);
        return $reArr;
    }

    //我的团队
    public function myTeam(){
        $u_id = $this->user['u_id'];
        $field= ['t.team_id,t.team_name,tm.team_mecha_name,ts.team_service_name,t.team_head'];
        Db::startTrans();
        try {
        $data = Db::name('support_team_user')
            ->alias('tu')
            ->leftJoin('ea_support_team t','tu.team_id = t.team_id and is_monitor=1')
            ->join('ea_support_team_mecha tm','t.team_mecha_id = tm.team_mecha_id','left')
            ->join('ea_support_team_service ts','t.team_service_id = ts.team_service_id','left')
            ->where('tu.user_id',$u_id)
            ->order('t.team_id desc')
            ->select();

            $list = [];
            foreach ($data as $v){
                $num = Db::name('support_team_user')->where('team_id',$v['team_id'])->count();
                $v['numpeo'] = $num;
                //已完成活动
                //正在进行中
               $list[] = $v;
            }


        } catch (\Exception $e) {
            //事务回滚
            Db::rollback();
            return $this->error('查询失败' . $e->getMessage());
        }


    }



    //发送验证码
    public function sendVerificationCode(){

        $GetData=input('post.');

        if(empty($GetData['u_phone'])){
            $this->error('缺少手机号');
        };
        //发送验证码
        $model=Env::get('verification.verification_bind_yanz');
        $request=$this->sendSms($GetData['u_phone'],$model);
        if($request['code']=1){
            $count = Db::name('support_code')->where(['code_phone'=>$GetData['u_phone']])->count();
            if($count>0){
                $map['code_phone']=$GetData['u_phone'];
                $map['code_status']=0;
                Db::name('support_code')->where($map)->update(['code_status'=>1]);
            }
            $SetData=[
                'code_phone'=>$GetData['u_phone'],
                'code_status'=>'0',
                'code'=>$request['authCode'],
                'create_time'=>time()
            ];
            Db::name('support_code')->insertGetId($SetData);
            $this->success('发送成功');
        }else{
            $this->error('msg');
        }

    }

    // 发送短息
    public static function sendSms($phoneNumber='',$model=''){

        $accessKeyId = Env::get('verification.verification_access_key');
        $accessSecret = Env::get('verification.verification_secret_key');
        $signName = Env::get('verification.verification_sing_name'); //配置签名
        $templateCode = $model;//配置短信模板编号
        $authCodeMT = mt_rand(100000,999999);
        $jsonTemplateParam = json_encode(['code'=>$authCodeMT]);

        AlibabaCloud::accessKeyClient($accessKeyId,$accessSecret)->regionId('cn-hangzhou')->asGlobalClient();
        try {
            $result = AlibabaCloud::rpcRequest()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->timeout(60)
                ->options([
                    'query' => [
                        'RegionId' => 'cn-hangzhou',
                        'PhoneNumbers' => $phoneNumber,//目标手机号
                        'SignName' => $signName,
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => $jsonTemplateParam,
                    ],
                ])
                ->request();

            $opRes = $result->toArray();

            if ($opRes && $opRes['Code'] == "OK"){
                return ['code'=>'1', 'msg'=>'发送成功','authCode'=>$authCodeMT];
            }else{
                return ['code'=>'0', 'msg'=>'发送失败'.$opRes['Code'],null,];
            }
        }catch (ClientException $e){
            return ['code'=>'0', 'msg'=>$e->getMessage(),'authCode'=>null];
        }catch (ServerException $e){
            return ['code'=>'0', 'msg'=>$e->getMessage(),'authCode'=>null];
        }
    }
}
