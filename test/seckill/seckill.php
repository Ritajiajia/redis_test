<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/16
 * Time: 13:36
 * ѹ�⣺-n ������ -c �����û�
 */
//��������
ini_set('display_errors', 'on');
error_reporting(E_ALL | E_STRICT);

header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

//Ϊ�˲��ԵĲ�����������100���û�ID�������У�Ȼ�������ȡһ��user_id
if(!$redis->sCard('userSet')) {
    for($i=1; $i <= 100; $i++) {
        $redis->sAdd('userSet', $i);
    }
}
$user_id = $redis->sRandMember('userSet');

//�����û��������
require './queueModel.php';
$queueModel = new queueModel($redis);
$result = $queueModel->enqueue('testGoods', $user_id);
echo "$result\r\n";die;

//�����������û�������ִ�м���棬ִ�гɹ��ͳ���
//ipƵ�����ƣ����Բ���Ҫ
/*$ip = getIP();//��ǰ�û�IP
$ipKey = "ip:limit:{$ip}";
$now = time();

//�ж�ip�����Ƿ񳬹�10��
$count = $redis->lLen($ipKey);
if($count < 10) {
    $redis->lPush($ipKey, $now);
} else {
    //ȡ����һ�η��ʵ�ʱ��
    $time = $redis->lIndex($ipKey, -1);

    if($now - $time < 60) {
        echo '���ʹ���Ƶ�����Ժ�����';
    } else {
        $redis->lPush($ipKey, $now);
        $redis->lTrim($ipKey, 0, 9);
    }
}

function getIP() {
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    }
    elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');

    }
    elseif (getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    }
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}*/