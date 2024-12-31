<?php

namespace Wegar\User\model\enum;

use Wegar\User\model\UserLogModel;

enum UserLogLevelEnum: string
{
  case INFO = UserLogModel::LEVEL_INFO;
  case WARNING = UserLogModel::LEVEL_WARNING;
  case NOTICE = UserLogModel::LEVEL_NOTICE;
}
