<?php

$basic_dir = __DIR__;
$connection = config('plugin.WegarUser.database.connection');
return [
  "paths"        => [
    "migrations" => is_phar()
      ? runtime_path(implode(DIRECTORY_SEPARATOR, [
        'phinx',
        'wegar-user',
        'database',
        'migrations'
      ]))
      : $basic_dir . DIRECTORY_SEPARATOR . "database/migrations",
    "seeds"      => is_phar()
      ? runtime_path(implode(DIRECTORY_SEPARATOR, [
        'phinx',
        'wegar-user',
        'database',
        'seeds'
      ]))
      : $basic_dir . DIRECTORY_SEPARATOR . "database/seeds",
  ],
  "environments" => [
    "default_migration_table" => "phinxlog",
    "default_environment"     => "default",
    "default"                 => [
      "adapter"   => config('plugin.WegarUser.database.adapter'),
      "host"      => config("database.connections.$connection.host"),
      "name"      => config("database.connections.$connection.database"),
      "user"      => config("database.connections.$connection.username"),
      "pass"      => config("database.connections.$connection.password"),
      "port"      => config("database.connections.$connection.port"),
      "charset"   => config("database.connections.$connection.charset", 'utf8mb4'),
      'collation' => config("database.connections.$connection.collation", 'utf8mb4_general_ci'),
    ],
  ]
];
