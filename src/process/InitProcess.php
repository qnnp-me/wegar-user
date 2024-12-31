<?php

namespace Wegar\User\process;

use Wegar\Basic\helper\InitHelper;

class InitProcess
{
  public function onWorkerStart(): void
  {
    InitHelper::loadInitFiles(
      dirname(__DIR__) . DIRECTORY_SEPARATOR . 'init',
      'Wegar\\User\\init\\'
    );
  }
}