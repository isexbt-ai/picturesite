<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    'commands' => [
        \app\common\command\CreateAdmin::class,
        \app\common\command\VipExpireCheck::class,
    ],
];
