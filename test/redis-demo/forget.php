<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/15
 * Time: 11:25
 * ͨ��redisʵ���������빦��
 */

//����Ƶ������
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

//�����޸������ʼ�

//��֤��
$verify_code = $_POST['code'];
$email = $_POST['email'];
$newPWD = bcryptHash($_POST['newPassword']);

$hashCode = bcryptHash($verify_code);
$redis->hmset('retrieve.password.code:'.$hashCode, ['email' => $email, 'newPassword' => $newPWD]);
//���ø���֤�������ʱ��
$redis->expire('retrieve.password.code:'.$hashCode, 24*60*60);

echo '�ʼ��ѷ���';
function bcryptHash($rawPassword, $round = 8)
{
    if($round < 4 || $round > 31) $round = 8;
    //str_padʹ����һ���ַ�������ַ���Ϊָ������:08
    $salt = '$2a$' . str_pad($round, 2, '0', STR_PAD_LEFT) . '$';
    //����һ��16�ֽڵ�α����ַ���
    $randomValue = openssl_random_pseudo_bytes(16);
    //base64_encode:����������б��롣strtr��ת��ָ���ַ�����substr�������ַ������Ӵ�
    $salt .= substr(strtr(base64_encode($randomValue), '+', '.'), 0, 22);
    return crypt($rawPassword, $salt);
}