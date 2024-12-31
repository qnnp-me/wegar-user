<?php

namespace Wegar\User\init;

use Phar;
use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\CommandHelper;

class ReleaseFiles extends InitAbstract
{
  public int $weight = 0;

  function run(): void
  {
    if (is_phar()) {
      $command_helper = new CommandHelper();
      $phar = Phar::running();
      if ($phar) {
        $target_dir = runtime_path('phinx' . DIRECTORY_SEPARATOR . 'wegar-user');
        if (!is_dir($target_dir)) {
          mkdir($target_dir, 0777, true);
        }
        $command_helper->notice("Releasing files...");
        $phar = new Phar($phar);
        $extract_list = [
          'vendor/wegar/user/src/database/' => $target_dir
        ];
        foreach ($extract_list as $from => $to) {
          $phar->extractTo($to, $from, true);
          $command_helper->info("Release $from to $to");
        }
        $command_helper->success("Release success.");
      }
    }
  }
}
