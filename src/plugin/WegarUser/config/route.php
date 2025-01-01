<?php

use plugin\WegarUser\app\api\controller\BasicController;
use Webman\Route;

Route::post('/app/wegar/user/api/login', [BasicController::class, 'login']);
Route::get('/app/wegar/user/api/info', [BasicController::class, 'info']);
Route::get('/app/wegar/user/api/logout', [BasicController::class, 'logout']);
Route::get('/app/wegar/user/api/captcha', [BasicController::class, 'captcha']);