<?php
/**
 * Created by PhpStorm.
 * User: chenjia
 * Date: 2016/11/15
 * Time: 11:48
 * 通过redis实现修改密码的功能
 */
//散列的验证码
$hashCode = $_GET['hasCode'];

//redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$email = $redis->hget('retrieve.password.code:'.$hashCode, 'email');

if(!$email) {
    echo '修改密码申请已失效';die;
}

$userID = $redis->hget('email.to.id', $email);

$newPassword = $redis->hget('retrieve.password.code:'.$hashCode, 'newPassword');
$redis->hset('user:'. $userID, 'password', $newPassword);

