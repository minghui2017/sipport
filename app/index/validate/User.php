<?php
declare (strict_types = 1);

namespace app\index\validate;

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
        'u_phone.require' => '用户手机号不能为空',
        'u_phone.number' => '用户手机格式错误',
        'u_phone.length' => '用户手机格式错误',
        'sex.require' =>'性别不能为空',
        'u_address' =>'地址不能为空'
    ];


    protected $scene = [
        'AddUser'=>['u_phone','u_name','sex','u_address'],
    ];
}
