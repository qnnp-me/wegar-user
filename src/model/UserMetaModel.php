<?php

namespace Wegar\User\model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property mixed $value
 * @property string $created_at
 * @property string $updated_at
 */
class UserMetaModel extends Model
{
  const NAME_VIP_TYPE = 'vip_type';
  const NAME_VIP_EXPIRE_AT = 'vip_expire_at';

  protected $table = 'user_metas';
  protected $casts = [
    'value' => 'json',
  ];

  function user(): BelongsTo
  {
    return $this->belongsTo(UserModel::class, 'user_id');
  }
}
