<?php

namespace Wegar\User\module;

use Exception;
use stdClass;
use support\exception\BusinessException;
use support\Log;
use Wegar\Basic\helper\SessionHelper;
use Wegar\User\model\enum\UserIdentifierTypeEnum;
use Wegar\User\model\enum\UserLogLevelEnum;
use Wegar\User\model\UserIdentifierModel;
use Wegar\User\model\UserLogContextModel;
use Wegar\User\model\UserLogModel;
use Wegar\User\model\UserMetaModel;
use Wegar\User\model\UserModel;
use Wegar\User\module\object\UserReadableObject;

class UserModule
{
  static function createUser(UserIdentifierTypeEnum $type, string $identifier): UserModel
  {
    $user_identifier = UserIdentifierModel::where('type', $type->value)->where('identifier', $identifier)->first();
    if ($user_identifier) {
      throw new BusinessException('用户已存在');
    }
    $user = new UserModel();
    $user
      ->fillable(['password', 'status'])
      ->fill([
        'password' => password_hash(uniqid(), PASSWORD_DEFAULT),
        'status'   => UserModel::STATUS_NORMAL,
      ])
      ->save();
    $user_identifier = new UserIdentifierModel();
    $user_identifier
      ->fillable(['user_id', 'type', 'identifier'])
      ->fill([
        'user_id'    => $user->id,
        'type'       => $type->value,
        'identifier' => $identifier,
      ])
      ->save();
    return $user;
  }

  /**
   * @param UserModel $user
   * @return UserReadableObject
   */
  static function toReadable(UserModel $user): object
  {
    $user_info = [];
    $user_info['id'] = $user->id;
    foreach ($user->identifiers as $identifier) {
      $user_info[$identifier->type] = $identifier->identifier;
    }
    $user_info['metas'] = [];
    foreach ($user->metas as $meta) {
      $user_info['metas'][$meta->name] = $meta->value;
    }
    //$user->status
    $user_info['status'] = $user->status;
    $user_info['created_at'] = $user->created_at;
    $user_info['updated_at'] = $user->updated_at;
    return json_decode(json_encode($user_info));
  }

  static function getUserById(int $id): UserModel
  {
    $user = UserModel::where('id', $id);
    if (!$user->exists()) {
      throw new BusinessException('用户不存在');
    }
    return $user
      ->with('identifiers')
      ->with('metas')
      ->first();
  }

  static function getUserByIdentifier(string $identifier): UserModel
  {
    $user_identifier = UserIdentifierModel::where('identifier', $identifier)->first();
    if (!$user_identifier) {
      throw new BusinessException('用户不存在');
    }
    return UserModel
      ::where('id', $user_identifier->user_id)
      ->with('identifiers')
      ->with('metas')
      ->first();
  }

  /**
   * @return UserReadableObject|null
   */
  static function getCurrentUser(): null|object
  {
    return ss()->userGet();
  }

  static function loginUserByIdentifier(string $identifier): void
  {
    $user = self::getUserByIdentifier($identifier);
    $user_info = self::toReadable($user);
    ss()->userSet($user_info);
  }

  static function loginUserById(int $id): void
  {
    $user = self::getUserById($id);
    $user_info = self::toReadable($user);
    ss()->userSet($user_info);
  }

  static function logoutUser(): void
  {
    ss()->userSet(null);
  }

  static function setMeta(string $name, mixed $value, int $user_id = null)
  {
    $user_id = $user_id ?? self::getCurrentUserId();
    $user = self::getUserById($user_id);
    $user_meta = $user->metas()->where('name', $name)->first();
    if ($user_meta) {
      $user_meta->value = $value;
      return $user_meta->save();
    } else {
      $user_meta = new UserMetaModel();
      return $user_meta
        ->fillable(['user_id', 'name', 'value'])
        ->fill([
          'user_id' => $user_id,
          'name'    => $name,
          'value'   => $value,
        ])
        ->save();
    }
  }

  static function log(string $initiator, UserLogLevelEnum $level, string $message, array $context, int $user_id = null): void
  {
    $user_id = $user_id ?? self::getCurrentUserId();
    try {
      $user_log = new UserLogModel();
      $user_log
        ->fillable(['user_id', 'initiator', 'level', 'message'])
        ->fill([
          'user_id'   => $user_id,
          'initiator' => $initiator,
          'level'     => $level->value,
          'message'   => $message,
        ])
        ->save();
      foreach ($context as $name => $value) {
        $user_log_context = new UserLogContextModel();
        $user_log_context
          ->fillable(['log_id', 'name', 'value'])
          ->fill([
            'log_id' => $user_log->id,
            'name'   => $name,
            'value'  => $value,
          ])
          ->save();
      }
    } catch (Exception $e) {
      Log::error("用户日志记录失败 -> {$e->getMessage()}");
    }
  }

  static function getLogs(int $page = 1, int $pageSize = null, bool $items_only = true, int $user_id = null)
  {
    $user_id = $user_id ?? self::getCurrentUserId();
    $logs = UserLogModel::where('user_id', $user_id)
      ->with('context')
      ->orderBy('created_at', 'desc')
      ->paginate($pageSize, page: $page);
    $data = array_map(function ($log) {
      $contexts = new stdClass();
      foreach ($log->context as $context) {
        $contexts->{$context->name} = $context->value;
      }
      $log = $log->toArray();
      $log['context'] = $contexts;
      return $log;
    }, $logs->items());
    return $items_only ? $data : [
      'data'  => $data,
      'total' => $logs->total()
    ];
  }

  private static function getCurrentUserId()
  {
    $user_info = self::getCurrentUser();
    if (!$user_info) {
      throw new BusinessException('用户未登录');
    }
    return $user_info->id;
  }
}
