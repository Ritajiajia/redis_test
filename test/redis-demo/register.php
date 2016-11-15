<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/14
 * Time: 17:58
 * desc: ͨ��redisʵ���û�ע�Ṧ��
 */
//����content-typeʹ���������ʹ����ȷ�ı�����ʾ��ʾ��Ϣ
header("Content-type: text/html; charset=utf-8");

if(!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['nickname']))
{
    echo 'Please fill in information';die;
}

$email = $_POST['email'];

//��֤�����Ƿ���ȷ
if(!filter_var($email, FILTER_VALIDATE_EMAIL))
{
    echo 'Please fill in correct email';die;
}

$nickname = $_POST['nickname'];

//redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

//�ж������Ƿ�ע���
if($redis->hExists('email.to.id', $email))
{
    echo 'This email has been used';die;
}

$password = $_POST['password'];
$hashPassword = bcryptHash($password);

//��ȡһ���������û�ID
$userID = $redis->incr('users:count');

//�洢�û���Ϣ
$redis->hmset('user:'.$userID, [
    'email' => $email,
    'password' => $hashPassword,
    'nickname' => $nickname
]);

//��¼�����û�ID�Ķ�Ӧ��ϵ
$redis->hset('email.to.id', $email, $userID);

//��ʾ�û�ע��ɹ�
echo 'success';

//������ܣ��������salt������crypt�������ɢ��
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