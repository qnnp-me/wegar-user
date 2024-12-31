<?php

use plugin\user\app\middleware\UserMiddleware;

return [
  '@' => [
    UserMiddleware::class
  ]
];
