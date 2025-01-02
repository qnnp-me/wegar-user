<?php

return [
  'device_key'     => '', // 设备号登陆时验证用的密钥
  'device_headers' => ['user-agent'], // 设备号登陆时验证用的头信息
  'delay'          => 2, // 验证码仅能在访问其他接口 *秒 之后才能获取
  'length'         => 4, // 验证码长度
  'email_template' => 'captcha', // 邮箱验证码模板名称
  'sms_tag'        => 'sms_code', // 短信验证码标签
];