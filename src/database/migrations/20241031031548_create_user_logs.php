<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUserLogs extends AbstractMigration
{
  public function change(): void
  {
    $table = $this->table('user_logs');
    $table
      ->addColumn('user_id', 'integer', ['null' => false, 'comment' => '关联用户ID'])
      ->addColumn('initiator', 'string', ['null' => false, 'comment' => '操作发起者'])
      ->addColumn('level', 'string', ['null' => false, 'comment' => '日志级别'])
      ->addColumn('message', 'text', ['null' => false, 'length' => MysqlAdapter::TEXT_LONG, 'comment' => '日志内容'])
      ->addTimestamps()
      ->addIndex('user_id')
      ->addIndex('initiator')
      ->addIndex('level')
      ->addIndex('created_at')
      ->save();
    if ($this->isMigratingUp()) {
      $table
        ->insert([
          [
            'user_id'   => 1,
            'initiator' => 'system',
            'level'     => 'info',
            'message'   => '创建用户',
          ]
        ])
        ->save();
    }
  }
}
