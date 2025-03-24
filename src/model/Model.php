<?php

namespace Wegar\User\model;

define("Wegar_User_model_connection", config('plugin.WegarUser.database.connection'));

class Model extends \support\Model
{
  protected $connection = Wegar_User_model_connection;
}