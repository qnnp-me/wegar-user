<?php

namespace plugin\WegarUser\app\api\controller;

use plugin\email\api\Email;
use plugin\sms\api\Sms;
use support\annotation\DisableDefaultRoute;
use support\Cache;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;
use Webman\RateLimiter\Annotation\RateLimiter;
use Wegar\User\model\enum\UserIdentifierTypeEnum;
use Wegar\User\model\enum\UserLogLevelEnum;
use Wegar\User\model\UserModel;
use Wegar\User\module\UserModule;

#[DisableDefaultRoute]
class BasicController
{
  #[RateLimiter(limit: 1, ttl: 2, message: '请求过于频繁，请稍后再试')]
  function login(string $identifier, string $password = '', string $code = '', string $sign = '', bool $create = false): Response
  {
    $user = UserModule::getUserByIdentifier($identifier);
    $userinfo = UserModule::toReadable($user);
    if ($password) {
      if (strtolower($code) !== session('captcha-login')) {
        throw new BusinessException('验证码错误');
      }
      session()->delete('captcha-login');
      if (!$user) {
        throw new BusinessException('用户不存在');
      }
      UserModule::log('system', UserLogLevelEnum::INFO, '用户登录', [
        'ip' => request()->getRealIp(false),
      ], $user->id);
      $password_verified = $user->verifyPassword($password);
      if (!$password_verified) {
        throw new BusinessException('密码错误');
      }
    } elseif ($code) {
      $captcha = Cache::get('captcha_' . preg_replace("#[^0-9a-zA-Z\-_.]#", "_", $identifier), [
        'code' => '',
        'time' => 0,
      ]);
      if ($captcha['code'] !== $code) {
        throw new BusinessException('验证码错误');
      }
      if (!isset($userinfo->phone) && !isset($userinfo->email)) {
        if ($create) {
          $user = UserModule::createUser(UserIdentifierTypeEnum::PHONE, $identifier);
          $userinfo = UserModule::toReadable($user);
          UserModule::log('system', UserLogLevelEnum::INFO, '用户创建', [
            'ip' => request()->getRealIp(false),
          ], $user->id);
        } else {
          throw new BusinessException('用户不存在');
        }
      }
    } elseif ($sign) {
      if (!isset($userinfo->device_id)) {
        if ($create) {
          $user = UserModule::createUser(UserIdentifierTypeEnum::PHONE, $identifier);
          $userinfo = UserModule::toReadable($user);
          UserModule::log('system', UserLogLevelEnum::INFO, '用户创建', [
            'ip' => request()->getRealIp(false),
          ], $user->id);
        } else {
          throw new BusinessException('用户不存在');
        }
      }
      $key = config('plugin.WegarUser.captcha.device_key');
      $header_keys = config('plugin.WegarUser.captcha.device_headers');
      sort($header_keys);
      $headers = '';
      foreach ($header_keys as $header_key) {
        $headers .= strtolower($header_key) . ':' . request()->header($header_key) . ',';
      }
      $device_sign_verified = md5($key . ',' . $headers . $identifier) === $sign;
      if (!$device_sign_verified) {
        throw new BusinessException('设备签名错误');
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
    return json_success(UserModule::logoutUser());
  }

  function captcha(Request $request, string $phone = '', string $email = '', $width = 150, $height = 40): Response
  {
    if (
      ($phone || $email) &&
      (time() - ss()->lastRequestTimeGet() < config('plugin.WegarUser.captcha.delay', 2))
    ) {
      throw new BusinessException('请求过于频繁，请稍后再试');
    }
    $len = config('plugin.WegarUser.captcha.length', 4);
    $min = pow(10, $len - 1);
    $max = pow(10, $len) - 1;
    $captcha = rand($min, $max);
    $cache_key = 'captcha_' . preg_replace("#[^0-9a-zA-Z\-_.]#", "_", $phone ?: $email ?: '');
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
      $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
      $captcha = new CaptchaBuilder(null, $builder);
      $captcha->build(min($width, 450), min($height, 120));
      $request->session()->set("captcha-login", strtolower($captcha->getPhrase()));
      $img_content = $captcha->get();
      return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }
    Cache::set($cache_key, [
      'code' => $captcha,
      'time' => time(),
    ], 5 * 60);
    return json_success();
  }
}