<?php

namespace plugin\user\app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Wegar\User\module\UserModule;

class UserMiddleware implements MiddlewareInterface
{
  public function process(Request $request, callable $handler): Response
  {
    $request->user = UserModule::getCurrentUser();
    /**
     * @var Response $response
     */
    $response = $handler($request);
    $response->withHeader('X-User-Id', $request->user?->id ?? 0);
    ss()->lastRequestTimeSet(time());
    return $response;
  }
}