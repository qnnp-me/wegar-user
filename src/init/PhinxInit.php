<?php

namespace Wegar\User\init;

use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\PhinxHelper;

class PhinxInit extends InitAbstract
{
  function run(): void
  {
    $basic_dir = dirname(__DIR__);
    $phinx_path = $basic_dir . DIRECTORY_SEPARATOR . 'phinx.php';
    PhinxHelper::load($phinx_path);
  }
}