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
use Webman\RateLimiter\Limiter;
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
      $cache_key = 'wegar-user-code-' . preg_replace("#[^0-9a-zA-Z\-_.]#", "_", $identifier ?: '');
      $last_code = Cache::get($cache_key, '');
      if ($last_code !== $code) {
        throw new BusinessException('验证码错误');
      }
      Cache::delete($cache_key);
      if (!isset($userinfo->phone) && !isset($userinfo->email)) {
        if ($create) {
          $user = UserModule::createUser(UserIdentifierTypeEnum::PHONE, $identifier);
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
    $current_user = UserModule::getCurrentUser();
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

  #[RateLimiter(limit: 10, ttl: 60, message: '请求过于频繁，请稍后再试')]
  function captcha(Request $request, string $phone = '', string $email = '', $width = 150, $height = 40): Response
  {
    if ($phone || $email) {
      if (time() - ss()->lastRequestTimeGet() < config('plugin.WegarUser.captcha.delay', 2)) {
        throw new BusinessException('请求过于频繁，请稍后再试');
      }

      $minutely_limit = config('plugin.WegarUser.captcha.minutely', 1);
      $daily_limit = config('plugin.WegarUser.captcha.daily', 10);
      Limiter::check(key: "wegar-user-captcha-minutely-" . ($phone ?: $email), limit: $minutely_limit, ttl: 60, message: "每分钟限制1次");
      Limiter::check(key: "wegar-user-captcha-daily-" . ($phone ?: $email), limit: $daily_limit, ttl: 60 * 60 * 24, message: "每个" . ($phone ? '手机' : '邮箱') . "每日限制" . $daily_limit . "次");

      $len = config('plugin.WegarUser.captcha.length', 4);
      $min = pow(10, $len - 1);
      $max = pow(10, $len) - 1;
      $code = rand($min, $max);
      $cache_key = 'wegar-user-code-' . preg_replace("#[^0-9a-zA-Z\-_.]#", "_", $phone ?: $email ?: '');
      if ($phone) {
        Sms::sendByTag($phone, config('plugin.WegarUser.captcha.sms_tag'), [
          'code' => $code,
        ]);
      } else {
        Email::sendByTemplate($email, config('plugin.WegarUser.captcha.email_template'), [
          'code' => $code,
        ]);
      }
      Cache::set($cache_key, $code, 5 * 60);
      return json_success();
    } else {
      $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
      $captcha = new CaptchaBuilder(null, $builder);
      $captcha->build(min($width, 450), min($height, 120));
      $request->session()->set("captcha-login", strtolower($captcha->getPhrase()));
      $img_content = $captcha->get();
      return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }
  }
}