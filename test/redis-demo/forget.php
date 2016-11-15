<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/15
 * Time: 11:25
 * 通过redis实现忘记密码功能
 */

//访问频率限制
$email = $_POST['email'];
$keyName = 'rate.limiting:'.$email;
$now = time();

//redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

if($redis->llen($keyName) < 10)
{
    $redis->lpush($keyName, $now);
} else {
    $time = $redis->lindex($keyName, -1);

    if($now - $time < 60) {
        echo 'More than the access restrictions, please try again later';die;
    } else {
        $redis->lpush($keyName, $now);
        $redis->ltrim($keyName, 0, 9);
    }
}

//发送修改密码邮件

//验证码
$verify_code = $_POST['code'];
$email = $_POST['email'];
$newPWD = bcryptHash($_POST['newPassword']);

$hashCode = bcryptHash($verify_code);
$redis->hmset('retrieve.password.code:'.$hashCode, ['email' => $email, 'newPassword' => $newPWD]);
//设置该验证码的生存时间
$redis->expire('retrieve.password.code:'.$hashCode, 24*60*60);

echo '邮件已发送';
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