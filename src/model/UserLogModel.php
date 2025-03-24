<?php

namespace Wegar\User\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $initiator
 * @property string $level
 * @property string $message
 * @property string $created_at
 * @property string $updated_at
 */
class UserLogModel extends Model
{
  const LEVEL_INFO = 'info';
  const LEVEL_WARNING = 'warning';
  const LEVEL_NOTICE = 'notice';

  protected $table = 'user_logs';

  function user(): BelongsTo
  {
    return $this->belongsTo(UserModel::class, 'user_id');
  }

  function context(): HasMany
  {
    return $this->hasMany(UserLogContextModel::class, 'log_id');
  }
}
