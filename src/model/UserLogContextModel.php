<?php

namespace Wegar\User\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

/**
 * @property int $id
 * @property int $log_id
 * @property string $name
 * @property mixed $value
 * @property string $created_at
 * @property string $updated_at
 */
class UserLogContextModel extends Model
{
  protected $table = 'user_log_contexts';
  protected $casts = [
    'value' => 'json',
  ];
  public $timestamps = false;

  function log(): BelongsTo
  {
    return $this->belongsTo(UserLogModel::class, 'log_id');
  }
}
