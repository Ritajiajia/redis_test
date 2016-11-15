<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/14
 * Time: 17:58
 * desc: 通过redis实现用户注册功能
 */
//设置content-type使浏览器可以使用正确的编码显示提示信息
header("Content-type: text/html; charset=utf-8");

if(!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['nickname']))
{
    echo 'Please fill in information';die;
}

$email = $_POST['email'];

//验证邮箱是否正确
if(!filter_var($email, FILTER_VALIDATE_EMAIL))
{
    echo 'Please fill in correct email';die;
}

$nickname = $_POST['nickname'];

//redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

//判断邮箱是否被注册过
if($redis->hExists('email.to.id', $email))
{
    echo 'This email has been used';die;
}

$password = $_POST['password'];
$hashPassword = bcryptHash($password);

//获取一个自增的用户ID
$userID = $redis->incr('users:count');

//存储用户信息
$redis->hmset('user:'.$userID, [
    'email' => $email,
    'password' => $hashPassword,
    'nickname' => $nickname
]);

//记录邮箱用户ID的对应关系
$redis->hset('email.to.id', $email, $userID);

//提示用户注册成功
echo 'success';

//密码加密：随机生成salt，调用crypt获得密码散列
function bcryptHash($rawPassword, $round = 8)
{
    if($round < 4 || $round > 31) $round = 8;
    //str_pad使用另一个字符串填充字符串为指定长度:08
    $salt = '$2a$' . str_pad($round, 2, '0', STR_PAD_LEFT) . '$';
    //生成一个16字节的伪随机字符串
    $randomValue = openssl_random_pseudo_bytes(16);
    //base64_encode:对随机数进行编码。strtr：转换指定字符串。substr：返回字符串的子串
    $salt .= substr(strtr(base64_encode($randomValue), '+', '.'), 0, 22);
    return crypt($rawPassword, $salt);
}