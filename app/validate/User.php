<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class User extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'u_name' =>'require',
        'team_name' =>'require',
        'team_mecha_id'=>'number|require',
        'team_tel' =>'require|number|length:11',
        'team_person'=>'require',
        'team_idnum' =>'require|number|length:18',
        'team_service_id'=>'number|require',
        'code'=>'require|number',
        'u_phone' => 'require|number|length:11',
        'sex'=>'require',
        'u_address'=>'require'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */
    protected $message = [
        'u_name.require' => '姓名不能为空',
        'team_name.require'=>'团队名不能为空',
        'team_mecha_id.require'=>'请选择团队归属',
        'team_service_id.require'=>'请选择团队服务类型',
        'team_tel.require'=>'手机号不能为空',
        'team_tel.number'=>'用户手机格式错误',
        'team_idnum.require'=>'身份证不能为空',
        'team_idnum.length'=>'身份证格式错误',
        'code.require'=>'验证码不能为空',
        'code.number'=>'验证码格式错误',
        'team_person.require'=>'负责人姓名不能为空',
        'u_phone.require' => '用户手机号不能为空',
        'u_phone.number' => '用户手机格式错误',
        'u_phone.length' => '用户手机格式错误',
        'sex.require' =>'性别不能为空',
        'u_address' =>'地址不能为空'
    ];


    protected $scene = [
        'AddUser'=>['u_phone','u_name','sex','u_address','code'],
        'AddTeam'=>['team_name','team_mecha_id','team_service_id','u_phone','team_idnum','team_person','code'],
    ];
}
