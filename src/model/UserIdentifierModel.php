<?php

namespace Wegar\User\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $identifier
 * @property string $created_at
 * @property string $updated_at
 */
class UserIdentifierModel extends Model
{
  const TYPE_USERNAME = 'username';
  const TYPE_PHONE = 'phone';
  const TYPE_DEVICE_ID = 'device_id';
  const TYPE_EMAIL = 'email';

  protected $table = 'user_identifiers';

  function user(): BelongsTo
  {
    return $this->belongsTo(UserModel::class, 'user_id');
  }
}
