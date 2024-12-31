<?php

use Wegar\User\process\InitProcess;

return [
  'init' => [
    'handler'     => InitProcess::class,
    'reloadable'  => false,
    'constructor' => [],
  ],
];