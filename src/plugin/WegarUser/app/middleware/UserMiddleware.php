<?php

namespace plugin\WegarUser\app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Wegar\User\module\UserModule;

class UserMiddleware implements MiddlewareInterface
{
  public function process(Request $request, callable $handler): Response
  {
    $user = UserModule::getCurrentUser();
    $request->wegarUser = $request->wegarUser ?? $user;
    /** @var Response $response */
    $response = $handler($request);
    $response->withHeader('X-User-Id', $user->id ?? 0);
    return $response;
  }
}