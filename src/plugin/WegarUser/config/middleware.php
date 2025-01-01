<?php

use plugin\WegarUser\app\middleware\UserMiddleware;

return [
  '@' => [
    UserMiddleware::class
  ]
];
