<?php
// 全局中间件定义文件
return [
    // Session 初始化（前台/接口共用 session 维持登录态）
    \think\middleware\SessionInit::class,
];
