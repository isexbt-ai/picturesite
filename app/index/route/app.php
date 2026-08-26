<?php
// +----------------------------------------------------------------------
// | 前台路由（index 应用）
// +----------------------------------------------------------------------
// | 注意：think-multi-app 下应用路由目录为 app/{应用}/route/，
// | 项目根 route/ 目录不会被多应用模式加载。
// +----------------------------------------------------------------------

use think\facade\Route;

Route::get('/', 'Index/index');
Route::get('album/:id', 'Album/detail');
Route::get('category/:slug', 'Category/index');
Route::get('tag/:slug', 'Tag/index');
Route::get('search', 'Search/index');
Route::get('login', 'Login/index');
Route::get('register', 'Register/index');
Route::get('user', 'User/index');
