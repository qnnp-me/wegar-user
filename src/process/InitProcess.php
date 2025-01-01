<?php

namespace Wegar\User\process;

use Wegar\Basic\helper\InitHelper;

class InitProcess
{
  public function onWorkerStart(): void
  {
    InitHelper::load(
      dirname(__DIR__) . DIRECTORY_SEPARATOR . 'init',
      'Wegar\\User\\init\\'
    );
  }
}