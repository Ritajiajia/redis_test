<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/16
 * Time: 11:24
 * ͨ��redisʵ�ֲַ�ʽ��
 */
class lockModel
{
    private $redis;//redis����
    private $lockNames = [];

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    /**
     *����
     * @param $name ����ʶ��
     * @param $timeout ѭ������ʧ�ܵĳ�ʱʱ��
     * @param $expire ��ǰ�����������ʱ�䣨�룩
     * @params $sleepTime ѭ���������ʧ�ܵ�����ʱ�䣨���룩
     * @return bool
     **/
    public function lock($name, $timeout = 0, $expire = 15, $sleepTime = 1000000)
    {
        date_default_timezone_set('Asia/Shanghai');

        if($name == null) return false;

        //ȡ�õ�ǰʱ��
        $now = time();
        //����ʧ�ܵȴ���ʱʱ��
        $timeoutAt = $now + $timeout;
        //��ǰ������ʱ��
        $expireAt = $now + $expire;

        $lockKey = "Lock:{$name}";

        while(true) {
            //�洢��ǰ���Ĺ���ʱ��
            $result = $this->redis->setnx($lockKey, $expireAt);
            if($result) {//�����ɹ�
                //���õ�ǰ���Ĺ���ʱ��
                $this->redis->expire($lockKey, $expireAt);
                //������־�浽������
                $this->lockNames[$name] = $expireAt;
                return true;
            }

            //����key��ʣ������ʱ��
            $ttl = $this->redis->ttl($lockKey);
            //keyû����������ʱ�䣬��������Ϊ����
            if($ttl < 0) {
                $this->redis->set($lockKey, $expireAt);
                $this->lockNames[$name] = $expireAt;
                return true;
            }

            //ѭ������
            if($timeout <= 0 || $timeoutAt < microtime(true)) break;

            //����$sleepTime�󣬼����������
            usleep($sleepTime);
        }
        return false;
    }

    /**
     * ����
     * @params $name ����ʶ
     * @return boolean
     **/
    public function unlock($name)
    {
        //�жϸ����Ƿ����
        if($this->isLocking($name)) {
            //ɾ����
            if($this->redis->del('Lock:'.$name)) {
                unset($this->lockNames[$name]);
                return true;
            }
        }
        return false;
    }

    /**
     * �ͷŵ�ǰ���л�õ���
     * @return boolean
     */
    public function unlockAll() {
        //�˱�־��������־�Ƿ��ͷ��������ɹ�
        $allSuccess = true;
        foreach ($this->lockedNames as $name => $expireAt) {
            if (false === $this->unlock($name)) {
                $allSuccess = false;
            }
        }
        return $allSuccess;
    }

    /**
     * ����ǰ������ָ������ʱ��
     * @param $name ����ʶ��
     * @param expire ����ʱ��
     * @return boolean
     **/
    public function expire($name, $expire)
    {
        if($this->isLocking($name)) {
            $expire = max($expire, 1);
            if($this->redis->expire("Lock:{$name}", $expire))
                return true;
        }
        return false;
    }

    /**
     * �жϵ�ǰ�Ƿ�ӵ��ָ�����ֵ���
     * @param $name ����ʶ��
     * return boolean
     **/
    public function isLocking($name)
    {
        if(isset($this->lockNames[$name])) {
            return $this->redis->get("Lock:{$name}") == $this->lockNames[$name];
        }
        return false;
    }
}