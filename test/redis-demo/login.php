<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/15
 * Time: 10:44
 *通过redis实现登陆功能
 */
header("Content-type: text/html; charset=utf-8");

if(!isset($_POST['email']) || !isset($_POST['password']))
{
    echo 'please fill in information';die;
}

$email = $_POST['email'];
$password = $_POST['password'];

//redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

//获得用户ID
$userID = $redis->hget('email.to.id', $email);

if(!$userID) {
    echo 'user does not exist';die;
}

$hashPassword = $redis->hget('user:'.$userID, 'password');

if(!bcryptVerify($password, $hashPassword)) {
    echo 'password does not correct';die;
}

echo 'success';
//密码验证
function bcryptVerify($rawPassword, $storedHash)
{
    return crypt($rawPassword, $storedHash) == $storedHash;
}

