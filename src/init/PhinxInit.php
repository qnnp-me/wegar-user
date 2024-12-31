<?php

namespace Wegar\User\init;

use Phinx\Console\PhinxApplication;
use Phinx\Wrapper\TextWrapper;
use Wegar\Basic\abstract\InitAbstract;
use Wegar\Basic\helper\CommandHelper;

class PhinxInit extends InitAbstract
{
  function run(): void
  {
    $basic_dir = dirname(__DIR__);
    $phinx_path = $basic_dir . DIRECTORY_SEPARATOR . 'phinx.php';
    $command_helper = new CommandHelper();
    $app = new PhinxApplication();
    $wrap = new TextWrapper($app);
    $wrap->setOption('configuration', $phinx_path);
    $config = include $phinx_path;

    $seed_path = $config['paths']['seeds'];
    $seeds = [];
    if (is_dir($seed_path)) {
      foreach (scandir($seed_path) as $file) {
        if (str_ends_with($file, '.php')) {
          $class_name = substr($file, 0, -4);
          if (class_exists($class_name)) {
            $seeds[] = "\\$class_name";
          }
        }
      }
    }

    $migrate_result = $wrap->getMigrate();
    $has_error = str_contains($migrate_result, 'Exception:');
    if ($has_error || str_contains($migrate_result, ' == ')) {
      $command_helper->notice('Wegar User Phinx Migrate');
      $command_helper->{$has_error ? 'error' : 'info'}(explode("\n", $migrate_result));
    }

    $seed_result = $wrap->getSeed(seed: $seeds);
    $has_error = str_contains($seed_result, 'Exception:');
    if ($has_error || str_contains($seed_result, ' == ')) {
      $command_helper->notice('Wegar User Phinx Seed');
      $command_helper->{$has_error ? 'error' : 'info'}(explode("\n", $seed_result));
    }
  }
}