<?php

namespace plugin\WegarUser\app\api\controller;

use plugin\email\api\Email;
use plugin\sms\api\Sms;
use support\annotation\DisableDefaultRoute;
use support\Cache;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Webman\RateLimiter\Annotation\RateLimiter;
use Wegar\User\model\enum\UserLogLevelEnum;
use Wegar\User\model\UserModel;
use Wegar\User\module\UserModule;

#[DisableDefaultRoute]
class BasicController
{
  #[RateLimiter(limit: 1, ttl: 2, message: '请求过于频繁，请稍后再试')]
  function login(string $identifier, string $password = '', string $code = ''): Response
  {
    $user = UserModule::getUserByIdentifier($identifier);
    if ($password) {
      $password_verified = $user->verifyPassword($password);
      if (!$password_verified) {
        throw new BusinessException('密码错误');
      }
    } elseif ($code) {
      $captcha = Cache::get('captcha_' . $identifier, [
        'code' => '',
        'time' => 0,
      ]);
      if ($captcha['code'] !== $code) {
        throw new BusinessException('验证码错误');
      }
    } else {
      throw new BusinessException('参数错误');
    }
    if ($user->status !== UserModel::STATUS_NORMAL) {
      throw new BusinessException('用户状态异常: ' . $user->status);
    }
    UserModule::log('system', UserLogLevelEnum::INFO, '用户登录', [
      'ip' => request()->getRealIp(false),
    ], $user->id);
    UserModule::loginUserById($user->id);
    return json_success(UserModule::toReadable($user));
  }

  function info(Request $request): Response
  {
    $current_user = $request->user;
    if (!$current_user) {
      throw new BusinessException('用户未登录');
    }
    $user = UserModule::getUserById($current_user->id);
    $current_user = UserModule::toReadable($user);
    ss()->userSet($current_user);
    return json_success($current_user);
  }

  function logout(): Response
  {
    UserModule::log('system', UserLogLevelEnum::INFO, '用户登出', [
      'ip' => request()->getRealIp(false),
    ]);
    UserModule::logoutUser();
    return json_success();
  }

  function captcha(Request $request, string $phone = '', string $email = ''): Response
  {
    if (time() - ss()->lastRequestTimeGet() < config('plugin.WegarUser.captcha.delay', 2)) {
      throw new BusinessException('请求过于频繁，请稍后再试');
    }
    $len = config('plugin.WegarUser.captcha.email_template', 4);
    $min = pow(10, $len - 1);
    $max = pow(10, $len) - 1;
    $captcha = rand($min, $max);
    $cache_key = 'captcha_' . ($phone ?: $email);
    $last_data = Cache::get($cache_key, [
      'code' => '',
      'time' => 0,
    ]);
    if (time() - $last_data['time'] < 60) {
      throw new BusinessException('请求过于频繁，请稍后再试');
    }
    if ($phone) {
      Sms::sendByTag($phone, config('plugin.WegarUser.captcha.sms_tag'), [
        'code' => $captcha,
      ]);
    } elseif ($email) {
      Email::sendByTemplate($email, config('plugin.WegarUser.captcha.email_template'), [
        'code' => $captcha,
      ]);
    } else {
      return json_error('参数错误');
    }
    Cache::set($cache_key, [
      'code' => $captcha,
      'time' => time(),
    ], 5 * 60);
    return json_success();
  }
}