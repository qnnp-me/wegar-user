<?php

use plugin\WegarUser\app\api\controller\BasicController;
use Webman\Route;

Route::post('/app/user/api/login', [BasicController::class, 'login']);
Route::get('/app/user/api/info', [BasicController::class, 'info']);
Route::get('/app/user/api/logout', [BasicController::class, 'logout']);
Route::get('/app/user/api/captcha', [BasicController::class, 'captcha']);