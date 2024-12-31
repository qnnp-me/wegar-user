<?php

namespace Wegar\User\model\enum;

use Wegar\User\model\UserIdentifierModel;

enum UserIdentifierTypeEnum: string
{
  case USERNAME  = UserIdentifierModel::TYPE_USERNAME;
  case PHONE     = UserIdentifierModel::TYPE_PHONE;
  case DEVICE_ID = UserIdentifierModel::TYPE_DEVICE_ID;
  case EMAIL     = UserIdentifierModel::TYPE_EMAIL;
}