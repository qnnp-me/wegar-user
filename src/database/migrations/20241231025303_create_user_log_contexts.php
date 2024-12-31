<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUserLogContexts extends AbstractMigration
{
  public function change(): void
  {
    $table = $this->table('user_log_contexts');
    $table
      ->addColumn('log_id', 'integer', ['null' => false, 'comment' => '关联日志ID'])
      ->addColumn('name', 'string', ['null' => false, 'comment' => '上下文名称'])
      ->addColumn('value', 'text', ['null' => false, 'length' => MysqlAdapter::TEXT_LONG, 'comment' => '上下文内容'])
      ->addIndex('log_id')
      ->addIndex('name')
      ->save();
    if ($this->isMigratingUp()) {
      $table
        ->insert([
          [
            'log_id' => 1,
            'name'   => 'user_id',
            'value'  => 1,
          ]
        ])
        ->save();
    }
  }
}
