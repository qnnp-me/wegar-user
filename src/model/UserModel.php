<?php

namespace Wegar\User\model;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property string $password
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class UserModel extends Model
{
  const STATUS_NORMAL = 'normal';
  const STATUS_BLOCKED = 'blocked';
  const STATUS_BANNED = 'banned';
  const STATUS_PENDING = 'pending';
  const STATUS_DISABLED = 'disabled';

  protected $table = 'users';

  function identifiers(): HasMany
  {
    return $this->hasMany(UserIdentifierModel::class, 'user_id');
  }

  function metas(): HasMany
  {
    return $this->hasMany(UserMetaModel::class, 'user_id');
  }

  function logs(): HasMany
  {
    return $this->hasMany(UserLogModel::class, 'user_id');
  }

  function verifyPassword(string $password): bool
  {
    if (!$this->password) {
      return false;
    }
    return password_verify($password, $this->password);
  }
}
