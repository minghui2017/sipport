<?php


return [

    // 不需要验证登录的控制器
    'no_login_controller' => [
        'login',
        'weixin'
    ],

    // 不需要验证登录的节点
    'no_login_node'       => [
        'login/index',
        'login/out',
        'weixin/index'
    ],

    // 不需要验证权限的控制器
    'no_auth_controller'  => [
        'ajax',
        'login',
        'index',
        'weixin'
    ],

    // 不需要验证权限的节点
    'no_auth_node'        => [
        'login/index',
        'login/out',
        'weixin/index'
    ],
];